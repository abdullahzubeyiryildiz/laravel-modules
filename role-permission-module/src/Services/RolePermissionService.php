<?php

namespace Modules\RolePermissionModule\Services;

use Modules\RolePermissionModule\Contracts\RolePermissionServiceInterface;
use Modules\RolePermissionModule\Models\Role;
use Modules\RolePermissionModule\Models\Permission;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RolePermissionService implements RolePermissionServiceInterface
{
    protected function getTenantId(): ?int
    {
        if (!config('role-permission-module.multi_tenant.enabled', false)) {
            return null;
        }

        $helperClass = config('role-permission-module.multi_tenant.tenant_helper_class', 'App\Helpers\TenantHelper');
        if (class_exists($helperClass) && method_exists($helperClass, 'id')) {
            return $helperClass::id();
        }

        return null;
    }

    /**
     * Kullanıcının rollerini al (cache'li).
     * Spatie laravel-permission mantığı: sadece user_id ile bağlı, tenant_id kullanılmaz.
     */
    public function getRoles(?Authenticatable $user, ?int $tenantId = null): array
    {
        if (!$user) {
            return [];
        }

        $cacheKey = "user_roles:{$user->id}";

        if (config('role-permission-module.cache.enabled', true)) {
            return Cache::remember($cacheKey, config('role-permission-module.cache.ttl', 3600), function () use ($user) {
                return $this->fetchUserRoles($user->id);
            });
        }

        return $this->fetchUserRoles($user->id);
    }

    /**
     * user_roles tablosundan sadece user_id ile roller (tenant_id filtresi yok).
     */
    protected function fetchUserRoles(int $userId): array
    {
        $roles = [];

        if (Schema::hasTable('user_roles')) {
            $roles = DB::table('user_roles')
                ->join('roles', 'user_roles.role_id', '=', 'roles.id')
                ->where('user_roles.user_id', $userId)
                ->where('roles.is_active', true)
                ->whereNull('roles.deleted_at')
                ->pluck('roles.slug')
                ->unique()
                ->values()
                ->toArray();
        }

        return array_values(array_unique($roles));
    }

    /**
     * Kullanıcının birincil rolünü al (user_id bazlı, tenant_id kullanılmaz).
     */
    public function getPrimaryRole(?Authenticatable $user, ?int $tenantId = null): ?string
    {
        $roles = $this->getRoles($user, $tenantId);

        if (empty($roles)) {
            if (isset($user->role) && $user->role) {
                return $user->role;
            }
            return null;
        }

        // En yüksek seviyeli rolü döndür (roller global, slug ile)
        $role = Role::whereIn('slug', $roles)
            ->orderByDesc('level')
            ->first();

        if ($role) {
            return $role->slug;
        }

        return $roles[0] ?? null;
    }

    /**
     * Kullanıcının rolü var mı?
     */
    public function hasRole(?Authenticatable $user, string|array $roles, ?int $tenantId = null): bool
    {
        $userRoles = $this->getRoles($user, $tenantId);

        if (empty($userRoles)) {
            return false;
        }

        $roles = is_array($roles) ? $roles : [$roles];

        return count(array_intersect(array_map('strtolower', $userRoles), array_map('strtolower', $roles))) > 0;
    }

    /**
     * Kullanıcının izni var mı?
     */
    public function hasPermission(?Authenticatable $user, string $permissionSlug, ?int $tenantId = null): bool
    {
        if (!$user) {
            return false;
        }

        // Super admin bypass
        if ($this->isSuperAdmin($user, $tenantId)) {
            return true;
        }

        $cacheKey = "user_permissions:{$user->id}";

        $permissions = config('role-permission-module.cache.enabled', true)
            ? Cache::remember($cacheKey, config('role-permission-module.cache.ttl', 3600), fn() => $this->fetchUserPermissions($user->id))
            : $this->fetchUserPermissions($user->id);

        return in_array($permissionSlug, $permissions) || in_array('*', $permissions);
    }

    protected function fetchUserPermissions(int $userId): array
    {
        return DB::table('user_roles')
            ->join('roles', 'user_roles.role_id', '=', 'roles.id')
            ->join('role_permissions', 'roles.id', '=', 'role_permissions.role_id')
            ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
            ->where('user_roles.user_id', $userId)
            ->where('roles.is_active', true)
            ->where('permissions.is_active', true)
            ->pluck('permissions.slug')
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Role ata (user_id bazlı, tenant_id kullanılmaz - Spatie mantığı).
     */
    public function assignRole(Authenticatable $user, string $roleSlug, ?int $tenantId = null): void
    {
        $role = Role::where('slug', $roleSlug)->first();

        if (!$role) {
            $this->seedDefaults(null);
            $role = Role::where('slug', $roleSlug)->first();
        }

        if (!$role) {
            return;
        }

        $exists = DB::table('user_roles')
            ->where('user_id', $user->id)
            ->where('role_id', $role->id)
            ->whereNull('tenant_id')
            ->exists();

        if (!$exists) {
            DB::table('user_roles')->insert([
                'user_id' => $user->id,
                'role_id' => $role->id,
                'tenant_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->clearUserCache($user->id);
        }
    }

    /**
     * Rolü kaldır (user_id bazlı).
     */
    public function removeRole(Authenticatable $user, string $roleSlug, ?int $tenantId = null): void
    {
        $role = Role::where('slug', $roleSlug)->first();

        if (!$role) {
            return;
        }

        DB::table('user_roles')
            ->where('user_id', $user->id)
            ->where('role_id', $role->id)
            ->delete();

        $this->clearUserCache($user->id);
    }

    /**
     * Rolleri senkronize et (user_id bazlı, tenant_id kullanılmaz - Spatie mantığı).
     */
    public function syncRoles(Authenticatable $user, array $roleSlugs, ?int $tenantId = null): void
    {
        $roles = Role::whereIn('slug', $roleSlugs)->pluck('id');

        DB::table('user_roles')->where('user_id', $user->id)->delete();

        foreach ($roles as $roleId) {
            DB::table('user_roles')->insert([
                'user_id' => $user->id,
                'role_id' => $roleId,
                'tenant_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->clearUserCache($user->id);
    }

    /**
     * Admin mi?
     */
    public function isAdmin(?Authenticatable $user, ?int $tenantId = null): bool
    {
        return $this->hasRole($user, config('role-permission-module.super_admin_slug', 'admin'), $tenantId);
    }

    /**
     * Super admin mi? (tüm izinlere sahip)
     */
    public function isSuperAdmin(?Authenticatable $user, ?int $tenantId = null): bool
    {
        return $this->isAdmin($user, $tenantId);
    }

    protected function clearUserCache(int $userId): void
    {
        Cache::forget("user_roles:{$userId}");
        Cache::forget("user_permissions:{$userId}");
    }

    /**
     * Mevcut rollerin slug listesini al (global roller: tenant_id null).
     */
    public function getAvailableRoleSlugs(?int $tenantId = null): array
    {
        return Role::where('is_active', true)
            ->pluck('slug')
            ->toArray();
    }

    /**
     * Varsayılan roller ve izinleri seed et
     */
    public function seedDefaults(?int $tenantId = null): void
    {
        $tenantId = $tenantId ?? $this->getTenantId();

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
            Role::firstOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }

        foreach ($permissions as $data) {
            Permission::firstOrCreate(
                ['tenant_id' => $tenantId, 'slug' => $data['slug']],
                array_merge($data, ['tenant_id' => $tenantId, 'is_system' => true])
            );
        }

        $adminRole = Role::where('slug', 'admin')->first();
        if ($adminRole) {
            $adminRole->permissions()->sync(Permission::where('tenant_id', $tenantId)->pluck('id'));
        }
    }
}
