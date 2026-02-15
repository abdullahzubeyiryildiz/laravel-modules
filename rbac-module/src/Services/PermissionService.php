<?php

namespace Modules\RbacModule\Services;

use Modules\RbacModule\Models\Role;
use Modules\RbacModule\Models\Permission;
use Modules\RbacModule\Models\TenantUser;
use Illuminate\Contracts\Auth\Authenticatable;

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

        // Admin her şeyi yapabilir
        if ($this->isAdmin($user)) {
            return true;
        }

        // TenantUser üzerinden kontrol et
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
     * TenantUser kaydını al
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
     */
    public function seedDefaultRolesAndPermissions(?int $tenantId = null): void
    {
        $tenantId = $tenantId ?? $this->getTenantId();

        // Varsayılan roller
        $roles = [
            [
                'name' => 'Admin',
                'slug' => 'admin',
                'display_name' => 'Yönetici',
                'level' => 100,
                'is_system' => true,
            ],
            [
                'name' => 'Manager',
                'slug' => 'manager',
                'display_name' => 'Yönetici',
                'level' => 50,
                'is_system' => true,
            ],
            [
                'name' => 'User',
                'slug' => 'user',
                'display_name' => 'Kullanıcı',
                'level' => 10,
                'is_system' => true,
            ],
        ];

        // Varsayılan permission'lar
        $permissions = [
            // Users
            ['name' => 'Users View', 'slug' => 'users.view', 'group' => 'users', 'display_name' => 'Kullanıcıları Görüntüle'],
            ['name' => 'Users Create', 'slug' => 'users.create', 'group' => 'users', 'display_name' => 'Kullanıcı Oluştur'],
            ['name' => 'Users Edit', 'slug' => 'users.edit', 'group' => 'users', 'display_name' => 'Kullanıcı Düzenle'],
            ['name' => 'Users Delete', 'slug' => 'users.delete', 'group' => 'users', 'display_name' => 'Kullanıcı Sil'],
            
            // Files
            ['name' => 'Files View', 'slug' => 'files.view', 'group' => 'files', 'display_name' => 'Dosyaları Görüntüle'],
            ['name' => 'Files Upload', 'slug' => 'files.upload', 'group' => 'files', 'display_name' => 'Dosya Yükle'],
            ['name' => 'Files Delete', 'slug' => 'files.delete', 'group' => 'files', 'display_name' => 'Dosya Sil'],
        ];

        foreach ($roles as $roleData) {
            $role = Role::firstOrCreate(
                ['tenant_id' => $tenantId, 'slug' => $roleData['slug']],
                array_merge($roleData, ['tenant_id' => $tenantId])
            );
        }

        foreach ($permissions as $permissionData) {
            $permission = Permission::firstOrCreate(
                ['tenant_id' => $tenantId, 'slug' => $permissionData['slug']],
                array_merge($permissionData, ['tenant_id' => $tenantId, 'is_system' => true])
            );
        }

        // Admin rolüne tüm permission'ları ata
        $adminRole = Role::where('tenant_id', $tenantId)->where('slug', 'admin')->first();
        if ($adminRole) {
            $allPermissions = Permission::where('tenant_id', $tenantId)->pluck('id');
            $adminRole->permissions()->sync($allPermissions);
        }
    }
}
