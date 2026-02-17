<?php

namespace Modules\RolePermissionModule\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\RolePermissionModule\Models\Role;
use Modules\RolePermissionModule\Services\RolePermissionService;

trait HasRoles
{
    /**
     * Roller ilişkisi
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'user_roles',
            'user_id',
            'role_id'
        )->withTimestamps();
    }

    /**
     * Rolü var mı?
     */
    public function hasRole(string|array $roles, ?int $tenantId = null): bool
    {
        return app(RolePermissionService::class)->hasRole($this, $roles, $tenantId);
    }

    /**
     * İzni var mı?
     */
    public function hasPermission(string $permission, ?int $tenantId = null): bool
    {
        return app(RolePermissionService::class)->hasPermission($this, $permission, $tenantId);
    }

    /**
     * Admin mi?
     */
    public function isAdmin(?int $tenantId = null): bool
    {
        return app(RolePermissionService::class)->isAdmin($this, $tenantId);
    }

    /**
     * Birincil rolü al
     */
    public function getPrimaryRole(?int $tenantId = null): ?string
    {
        return app(RolePermissionService::class)->getPrimaryRole($this, $tenantId);
    }

    /**
     * Tüm rollerini al
     */
    public function getRoles(?int $tenantId = null): array
    {
        return app(RolePermissionService::class)->getRoles($this, $tenantId);
    }

    /**
     * Role ata
     */
    public function assignRole(string $roleSlug, ?int $tenantId = null): void
    {
        app(RolePermissionService::class)->assignRole($this, $roleSlug, $tenantId);
    }

    /**
     * Rolü kaldır
     */
    public function removeRole(string $roleSlug, ?int $tenantId = null): void
    {
        app(RolePermissionService::class)->removeRole($this, $roleSlug, $tenantId);
    }

    /**
     * Rolleri senkronize et
     */
    public function syncRoles(array $roleSlugs, ?int $tenantId = null): void
    {
        app(RolePermissionService::class)->syncRoles($this, $roleSlugs, $tenantId);
    }
}
