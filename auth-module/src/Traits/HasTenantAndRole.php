<?php

namespace Modules\AuthModule\Traits;

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
        $role = $this->getUserRole();
        return $role === 'admin';
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
    protected function getUserRole(): ?string
    {
        // User model'inde role attribute'u varsa (en yaygın kullanım)
        if (isset($this->role)) {
            return $this->role;
        }

        // User model'inde getRole() metodu varsa
        if (method_exists($this, 'getRole')) {
            return $this->getRole();
        }

        // User model'inde role() relationship'i varsa
        if (method_exists($this, 'role')) {
            try {
                $role = $this->role();
                if ($role) {
                    return is_object($role) ? ($role->name ?? $role->slug ?? null) : $role;
                }
            } catch (\Exception $e) {
                // Relationship yoksa veya hata varsa devam et
            }
        }

        // User model'inde toArray() ile kontrol et
        if (method_exists($this, 'toArray')) {
            $attributes = $this->toArray();
            if (isset($attributes['role'])) {
                return $attributes['role'];
            }
        }

        return null;
    }
}
