<?php

namespace Modules\AuthModule\Helpers;

use Illuminate\Contracts\Auth\Authenticatable;
use Modules\AuthModule\Services\RoleService;

/**
 * Role helper functions
 */
class RoleHelper
{
    /**
     * Kullanıcının rolünü kontrol et
     */
    public static function hasRole(?Authenticatable $user, string|array $roles): bool
    {
        $roleService = app(RoleService::class);
        return $roleService->hasRole($user, $roles);
    }

    /**
     * Kullanıcı admin mi?
     */
    public static function isAdmin(?Authenticatable $user): bool
    {
        $roleService = app(RoleService::class);
        return $roleService->isAdmin($user);
    }

    /**
     * Kullanıcı manager mi?
     */
    public static function isManager(?Authenticatable $user): bool
    {
        $roleService = app(RoleService::class);
        return $roleService->isManager($user);
    }

    /**
     * Kullanıcının rolünü al
     */
    public static function getRole(?Authenticatable $user): ?string
    {
        $roleService = app(RoleService::class);
        return $roleService->getUserRole($user);
    }

    /**
     * Kullanıcının rolünü formatla
     */
    public static function getFormattedRole(?Authenticatable $user): string
    {
        $roleService = app(RoleService::class);
        return $roleService->getFormattedRole($user);
    }

    /**
     * Yetki kontrolü
     */
    public static function can(?Authenticatable $user, string $permission): bool
    {
        $roleService = app(RoleService::class);
        return $roleService->can($user, $permission);
    }
}
