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
     * Kullanıcının rollerini al (cache'li)
     */
    public function getRoles(?Authenticatable $user, ?int $tenantId = null): array
    {
        if (!$user) {
            return [];
        }

        $tenantId = $tenantId ?? $this->getTenantId();
        $cacheKey = "user_roles:{$user->id}:{$tenantId}";

        if (config('role-permission-module.cache.enabled', true)) {
            return Cache::remember($cacheKey, config('role-permission-module.cache.ttl', 3600), function () use ($user, $tenantId) {
                return $this->fetchUserRoles($user->id, $tenantId);
            });
        }

        return $this->fetchUserRoles($user->id, $tenantId);
    }

    protected function fetchUserRoles(int $userId, ?int $tenantId): array
    {
        $roles = [];

        // user_roles tablosundan al
        if (Schema::hasTable('user_roles')) {
            $query = DB::table('user_roles')
                ->join('roles', 'user_roles.role_id', '=', 'roles.id')
                ->where('user_roles.user_id', $userId)
                ->where('roles.is_active', true);

            if ($tenantId !== null) {
                $query->where(function ($q) use ($tenantId) {
                    $q->where('user_roles.tenant_id', $tenantId)
                        ->orWhereNull('user_roles.tenant_id');
                });
            } else {
                $query->whereNull('user_roles.tenant_id');
            }

            $roles = $query->pluck('roles.slug')->unique()->values()->toArray();
        }

        // tenant_users'tan fallback (rbac-module legacy)
        if (empty($roles) && $tenantId && Schema::hasTable('tenant_users')) {
            $tenantUser = DB::table('tenant_users')
                ->join('roles', 'tenant_users.role_id', '=', 'roles.id')
                ->where('tenant_users.user_id', $userId)
                ->where('tenant_users.tenant_id', $tenantId)
                ->where('tenant_users.is_active', true)
                ->select('roles.slug')
                ->first();
            if ($tenantUser) {
                $roles = [$tenantUser->slug];
            }
        }

        return array_values(array_unique($roles));
    }

    /**
     * Kullanıcının birincil rolünü al
     */
    public function getPrimaryRole(?Authenticatable $user, ?int $tenantId = null): ?string
    {
        $roles = $this->getRoles($user, $tenantId);

        if (empty($roles)) {
            // Eski users.role kolonu desteği (geçiş dönemi)
            if (isset($user->role) && $user->role) {
                return $user->role;
            }
            return null;
        }

        // En yüksek seviyeli rolü döndür
        $tenantId = $tenantId ?? $this->getTenantId();
        $role = Role::whereIn('slug', $roles)
            ->when($tenantId !== null, fn($q) => $q->where(fn($q2) => $q2->where('tenant_id', $tenantId)->orWhereNull('tenant_id')))
            ->when($tenantId === null, fn($q) => $q->whereNull('tenant_id'))
            ->orderByDesc('level')
            ->first();

        return $role?->slug;
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

        $tenantId = $tenantId ?? $this->getTenantId();
        $cacheKey = "user_permissions:{$user->id}:{$tenantId}";

        $permissions = config('role-permission-module.cache.enabled', true)
            ? Cache::remember($cacheKey, config('role-permission-module.cache.ttl', 3600), fn() => $this->fetchUserPermissions($user->id, $tenantId))
            : $this->fetchUserPermissions($user->id, $tenantId);

        return in_array($permissionSlug, $permissions) || in_array('*', $permissions);
    }

    protected function fetchUserPermissions(int $userId, ?int $tenantId): array
    {
        $query = DB::table('user_roles')
            ->join('roles', 'user_roles.role_id', '=', 'roles.id')
            ->join('role_permissions', 'roles.id', '=', 'role_permissions.role_id')
            ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
            ->where('user_roles.user_id', $userId)
            ->where('roles.is_active', true)
            ->where('permissions.is_active', true);

        if ($tenantId !== null) {
            $query->where(function ($q) use ($tenantId) {
                $q->where('user_roles.tenant_id', $tenantId)->orWhereNull('user_roles.tenant_id');
            });
        } else {
            $query->whereNull('user_roles.tenant_id');
        }

        return $query->pluck('permissions.slug')->unique()->values()->toArray();
    }

    /**
     * Role ata
     */
    public function assignRole(Authenticatable $user, string $roleSlug, ?int $tenantId = null): void
    {
        $tenantId = $tenantId ?? $this->getTenantId();
        $role = Role::where('slug', $roleSlug)
            ->when($tenantId !== null, fn($q) => $q->where(fn($q2) => $q2->where('tenant_id', $tenantId)->orWhereNull('tenant_id')))
            ->when($tenantId === null, fn($q) => $q->whereNull('tenant_id'))
            ->first();

        if (!$role) {
            $this->seedDefaults($tenantId);
            $role = Role::where('slug', $roleSlug)
                ->when($tenantId !== null, fn($q) => $q->where(fn($q2) => $q2->where('tenant_id', $tenantId)->orWhereNull('tenant_id')))
                ->when($tenantId === null, fn($q) => $q->whereNull('tenant_id'))
                ->first();
        }

        if (!$role) {
            return;
        }

        $exists = DB::table('user_roles')
            ->where('user_id', $user->id)
            ->where('role_id', $role->id)
            ->where('tenant_id', $tenantId)
            ->exists();

        if (!$exists) {
            DB::table('user_roles')->insert([
                'user_id' => $user->id,
                'role_id' => $role->id,
                'tenant_id' => $tenantId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->clearUserCache($user->id, $tenantId);
        }
    }

    /**
     * Rolü kaldır
     */
    public function removeRole(Authenticatable $user, string $roleSlug, ?int $tenantId = null): void
    {
        $tenantId = $tenantId ?? $this->getTenantId();
        $role = Role::where('slug', $roleSlug)->first();

        if (!$role) {
            return;
        }

        DB::table('user_roles')
            ->where('user_id', $user->id)
            ->where('role_id', $role->id)
            ->where('tenant_id', $tenantId)
            ->delete();

        $this->clearUserCache($user->id, $tenantId);
    }

    /**
     * Rolleri senkronize et
     */
    public function syncRoles(Authenticatable $user, array $roleSlugs, ?int $tenantId = null): void
    {
        $tenantId = $tenantId ?? $this->getTenantId();

        $roles = Role::whereIn('slug', $roleSlugs)
            ->when($tenantId !== null, fn($q) => $q->where(fn($q2) => $q2->where('tenant_id', $tenantId)->orWhereNull('tenant_id')))
            ->when($tenantId === null, fn($q) => $q->whereNull('tenant_id'))
            ->pluck('id');

        DB::table('user_roles')
            ->where('user_id', $user->id)
            ->where('tenant_id', $tenantId)
            ->delete();

        foreach ($roles as $roleId) {
            DB::table('user_roles')->insert([
                'user_id' => $user->id,
                'role_id' => $roleId,
                'tenant_id' => $tenantId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->clearUserCache($user->id, $tenantId);
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

    protected function clearUserCache(int $userId, ?int $tenantId): void
    {
        Cache::forget("user_roles:{$userId}:{$tenantId}");
        Cache::forget("user_permissions:{$userId}:{$tenantId}");
    }

    /**
     * Mevcut rollerin slug listesini al (dinamik)
     */
    public function getAvailableRoleSlugs(?int $tenantId = null): array
    {
        $tenantId = $tenantId ?? $this->getTenantId();

        return Role::when($tenantId !== null, fn($q) => $q->where(fn($q2) => $q2->where('tenant_id', $tenantId)->orWhereNull('tenant_id')))
            ->when($tenantId === null, fn($q) => $q->whereNull('tenant_id'))
            ->where('is_active', true)
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
                ['tenant_id' => $tenantId, 'slug' => $data['slug']],
                array_merge($data, ['tenant_id' => $tenantId])
            );
        }

        foreach ($permissions as $data) {
            Permission::firstOrCreate(
                ['tenant_id' => $tenantId, 'slug' => $data['slug']],
                array_merge($data, ['tenant_id' => $tenantId, 'is_system' => true])
            );
        }

        $adminRole = Role::where('tenant_id', $tenantId)->where('slug', 'admin')->first();
        if ($adminRole) {
            $adminRole->permissions()->sync(Permission::where('tenant_id', $tenantId)->pluck('id'));
        }
    }
}
