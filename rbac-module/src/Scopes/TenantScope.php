<?php

namespace Modules\RbacModule\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = $this->getTenantId();

        if ($tenantId && $model->getTable() !== 'tenants') {
            $builder->where($model->getTable() . '.tenant_id', $tenantId);
        }
    }

    /**
     * Extend the query builder with the needed functions.
     */
    public function extend(Builder $builder): void
    {
        $builder->macro('withoutTenantScope', function (Builder $builder) {
            return $builder->withoutGlobalScope(self::class);
        });
    }

    /**
     * Tenant ID'yi al
     */
    protected function getTenantId(): ?int
    {
        // Request'ten al
        if (request()->has('tenant_id')) {
            return request()->get('tenant_id');
        }

        // Config'den al
        $tenantId = config('tenant.id');
        if ($tenantId) {
            return $tenantId;
        }

        // Session'dan al
        $tenantId = session('tenant_id');
        if ($tenantId) {
            return $tenantId;
        }

        // TenantHelper'dan al
        if (config('auth-module.multi_tenant.enabled', false)) {
            $tenantHelperClass = config('auth-module.multi_tenant.tenant_helper_class', 'App\Helpers\TenantHelper');
            if (class_exists($tenantHelperClass) && method_exists($tenantHelperClass, 'id')) {
                return $tenantHelperClass::id();
            }
        }

        return null;
    }
}
