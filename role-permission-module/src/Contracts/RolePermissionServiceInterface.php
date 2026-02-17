<?php

namespace Modules\RolePermissionModule\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface RolePermissionServiceInterface
{
    public function hasRole(?Authenticatable $user, string|array $roles, ?int $tenantId = null): bool;

    public function hasPermission(?Authenticatable $user, string $permissionSlug, ?int $tenantId = null): bool;

    public function getPrimaryRole(?Authenticatable $user, ?int $tenantId = null): ?string;

    public function getRoles(?Authenticatable $user, ?int $tenantId = null): array;

    public function assignRole(Authenticatable $user, string $roleSlug, ?int $tenantId = null): void;

    public function removeRole(Authenticatable $user, string $roleSlug, ?int $tenantId = null): void;

    public function syncRoles(Authenticatable $user, array $roleSlugs, ?int $tenantId = null): void;

    public function isAdmin(?Authenticatable $user, ?int $tenantId = null): bool;

    public function isSuperAdmin(?Authenticatable $user, ?int $tenantId = null): bool;
}
