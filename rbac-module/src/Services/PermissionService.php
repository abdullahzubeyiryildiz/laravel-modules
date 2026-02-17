<?php

namespace Modules\RbacModule\Services;

use Modules\RbacModule\Models\TenantUser;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * İzin servisi - role-permission-module kullanır (yüklüyse).
 * role-permission-module yoksa tenant_users üzerinden legacy kontrol yapar.
 */
class PermissionService
{
    /**
     * Kullanıcının permission'ı var mı?
     */
    public function hasPermission(?Authenticatable $user, string $permissionSlug): bool
    {
        if (!$user) {
            return false;
        }

        // role-permission-module varsa onu kullan
        if (config('role-permission-module.enabled', false)
            && class_exists(\Modules\RolePermissionModule\Services\RolePermissionService::class)) {
            $tenantId = $this->getTenantId();
            return app(\Modules\RolePermissionModule\Services\RolePermissionService::class)
                ->hasPermission($user, $permissionSlug, $tenantId);
        }

        // Legacy: TenantUser üzerinden kontrol
        $tenantUser = $this->getTenantUser($user);
        if ($tenantUser && $tenantUser->role) {
            return $tenantUser->role->hasPermission($permissionSlug);
        }

        return false;
    }

    /**
     * Kullanıcının rolü var mı?
     */
    public function hasRole(?Authenticatable $user, string|array $roles): bool
    {
        if (!$user) {
            return false;
        }

        if (config('role-permission-module.enabled', false)
            && class_exists(\Modules\RolePermissionModule\Services\RolePermissionService::class)) {
            $tenantId = $this->getTenantId();
            return app(\Modules\RolePermissionModule\Services\RolePermissionService::class)
                ->hasRole($user, $roles, $tenantId);
        }

        $tenantUser = $this->getTenantUser($user);
        if (!$tenantUser || !$tenantUser->role) {
            return false;
        }

        if (is_array($roles)) {
            return in_array($tenantUser->role->slug, $roles);
        }

        return $tenantUser->role->slug === $roles;
    }

    /**
     * Kullanıcı admin mi?
     */
    public function isAdmin(?Authenticatable $user): bool
    {
        return $this->hasRole($user, 'admin');
    }

    /**
     * TenantUser kaydını al (legacy)
     */
    protected function getTenantUser(Authenticatable $user): ?TenantUser
    {
        $tenantId = $this->getTenantId();
        if (!$tenantId) {
            return null;
        }

        return TenantUser::where('tenant_id', $tenantId)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();
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
     * Varsayılan rolleri ve permission'ları oluştur
     * role-permission-module varsa onun servisini kullanır
     */
    public function seedDefaultRolesAndPermissions(?int $tenantId = null): void
    {
        $tenantId = $tenantId ?? $this->getTenantId();

        if (config('role-permission-module.enabled', false)
            && class_exists(\Modules\RolePermissionModule\Services\RolePermissionService::class)) {
            app(\Modules\RolePermissionModule\Services\RolePermissionService::class)->seedDefaults($tenantId);
            return;
        }

        // Legacy - RbacModule modelleri ile (Role, Permission)
        if (!class_exists(\Modules\RbacModule\Models\Role::class)) {
            return;
        }

        $roleClass = \Modules\RbacModule\Models\Role::class;
        $permissionClass = \Modules\RbacModule\Models\Permission::class;

        $roles = [
            ['name' => 'Admin', 'slug' => 'admin', 'display_name' => 'Yönetici', 'level' => 100, 'is_system' => true],
            ['name' => 'Manager', 'slug' => 'manager', 'display_name' => 'Yönetici', 'level' => 50, 'is_system' => true],
            ['name' => 'User', 'slug' => 'user', 'display_name' => 'Kullanıcı', 'level' => 10, 'is_system' => true],
        ];

        $permissions = [
            ['name' => 'Users View', 'slug' => 'users.view', 'group' => 'users', 'display_name' => 'Kullanıcıları Görüntüle'],
            ['name' => 'Users Create', 'slug' => 'users.create', 'group' => 'users', 'display_name' => 'Kullanıcı Oluştur'],
            ['name' => 'Users Edit', 'slug' => 'users.edit', 'group' => 'users', 'display_name' => 'Kullanıcı Düzenle'],
            ['name' => 'Users Delete', 'slug' => 'users.delete', 'group' => 'users', 'display_name' => 'Kullanıcı Sil'],
        ];

        foreach ($roles as $data) {
            $roleClass::firstOrCreate(
                ['tenant_id' => $tenantId, 'slug' => $data['slug']],
                array_merge($data, ['tenant_id' => $tenantId])
            );
        }

        foreach ($permissions as $data) {
            $permissionClass::firstOrCreate(
                ['tenant_id' => $tenantId, 'slug' => $data['slug']],
                array_merge($data, ['tenant_id' => $tenantId, 'is_system' => true])
            );
        }

        $adminRole = $roleClass::where('tenant_id', $tenantId)->where('slug', 'admin')->first();
        if ($adminRole) {
            $adminRole->permissions()->sync($permissionClass::where('tenant_id', $tenantId)->pluck('id'));
        }
    }
}
