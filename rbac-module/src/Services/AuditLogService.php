<?php

namespace Modules\RbacModule\Services;

use Modules\RbacModule\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogService
{
    /**
     * Audit log kaydet
     */
    public function log(
        string $action,
        string $entity,
        ?int $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $meta = null,
        ?int $executionTimeMs = null
    ): AuditLog {
        $request = request();
        $user = Auth::user();
        $tenantId = $this->getTenantId();

        return AuditLog::create([
            'tenant_id' => $tenantId,
            'user_id' => $user?->id,
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'meta' => $meta,
            'execution_time_ms' => $executionTimeMs,
            'created_at' => now(),
        ]);
    }

    /**
     * Tenant ID'yi al
     */
    protected function getTenantId(): ?int
    {
        if (config('auth-module.multi_tenant.enabled', false)) {
            $tenantHelperClass = config('auth-module.multi_tenant.tenant_helper_class', 'App\Helpers\TenantHelper');
            if (class_exists($tenantHelperClass) && method_exists($tenantHelperClass, 'id')) {
                return $tenantHelperClass::id();
            }
        }

        return null;
    }

    /**
     * Model değişikliklerini logla
     */
    public function logModelChange(
        string $action,
        Model $model,
        ?array $oldValues = null,
        ?array $meta = null
    ): AuditLog {
        $newValues = $model->getChanges();
        
        return $this->log(
            $action,
            class_basename($model),
            $model->id,
            $oldValues,
            $newValues ?: null,
            $meta
        );
    }

    /**
     * Query logları (tenant bazlı)
     */
    public function getLogs(
        ?string $entity = null,
        ?string $action = null,
        ?int $userId = null,
        ?int $limit = 100
    ) {
        $query = AuditLog::query();
        $tenantId = $this->getTenantId();

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($entity) {
            $query->where('entity', $entity);
        }

        if ($action) {
            $query->where('action', $action);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
