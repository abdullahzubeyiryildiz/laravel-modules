<?php

namespace Modules\RolePermissionModule\Helpers;

use Illuminate\Contracts\Auth\Authenticatable;
use Modules\RolePermissionModule\Services\RolePermissionService;

class RolePermissionHelper
{
    public static function hasRole(?Authenticatable $user, string|array $roles, ?int $tenantId = null): bool
    {
        return app(RolePermissionService::class)->hasRole($user, $roles, $tenantId);
    }

    public static function hasPermission(?Authenticatable $user, string $permission, ?int $tenantId = null): bool
    {
        return app(RolePermissionService::class)->hasPermission($user, $permission, $tenantId);
    }

    public static function isAdmin(?Authenticatable $user, ?int $tenantId = null): bool
    {
        return app(RolePermissionService::class)->isAdmin($user, $tenantId);
    }

    public static function getPrimaryRole(?Authenticatable $user, ?int $tenantId = null): ?string
    {
        return app(RolePermissionService::class)->getPrimaryRole($user, $tenantId);
    }

    public static function getRoles(?Authenticatable $user, ?int $tenantId = null): array
    {
        return app(RolePermissionService::class)->getRoles($user, $tenantId);
    }
}
