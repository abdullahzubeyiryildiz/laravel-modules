<?php

namespace Modules\AuthModule\Traits;

/**
 * Tenant ilişkisi ve rol desteği.
 * Rol kontrolleri role-permission-module kullanılır (yüklüyse).
 * Modül yoksa users.role kolonu fallback olarak kullanılır.
 */
trait HasTenantAndRole
{
    /**
     * Kullanıcının ait olduğu tenant
     */
    public function tenant()
    {
        $tenantModel = config('auth-module.multi_tenant.tenant_model', 'App\Models\Tenant');

        if (class_exists($tenantModel)) {
            return $this->belongsTo($tenantModel);
        }

        return null;
    }

    /**
     * Kullanıcı admin mi?
     */
    public function isAdmin(): bool
    {
        return $this->getRoleChecker()->isAdmin($this);
    }

    /**
     * Kullanıcı manager mı?
     */
    public function isManager(): bool
    {
        $role = $this->getUserRole();
        return in_array($role, ['admin', 'manager']);
    }

    /**
     * Kullanıcının rolünü al
     */
    public function getUserRole(): ?string
    {
        return $this->getRoleChecker()->getPrimaryRole($this);
    }

    /**
     * Rol/izin kontrol servisini al (role-permission-module veya fallback)
     */
    protected function getRoleChecker()
    {
        if (config('role-permission-module.enabled', false)
            && class_exists(\Modules\RolePermissionModule\Services\RolePermissionService::class)) {
            return app(\Modules\RolePermissionModule\Services\RolePermissionService::class);
        }

        return app(\Modules\AuthModule\Services\RoleFallbackService::class);
    }
}
