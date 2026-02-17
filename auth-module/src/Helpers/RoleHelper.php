<?php

namespace Modules\AuthModule\Helpers;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Rol yardımcı fonksiyonları.
 * role-permission-module yüklüyse onu kullanır, değilse users.role kolonuna fallback yapar.
 */
class RoleHelper
{
    protected static function getService()
    {
        if (config('role-permission-module.enabled', false)
            && class_exists(\Modules\RolePermissionModule\Services\RolePermissionService::class)) {
            return app(\Modules\RolePermissionModule\Services\RolePermissionService::class);
        }
        return app(\Modules\AuthModule\Services\RoleFallbackService::class);
    }

    public static function hasRole(?Authenticatable $user, string|array $roles): bool
    {
        return static::getService()->hasRole($user, $roles);
    }

    public static function isAdmin(?Authenticatable $user): bool
    {
        return static::getService()->isAdmin($user);
    }

    public static function isManager(?Authenticatable $user): bool
    {
        $role = static::getRole($user);
        return in_array($role, ['admin', 'manager']);
    }

    public static function getRole(?Authenticatable $user): ?string
    {
        return static::getService()->getPrimaryRole($user);
    }

    public static function getFormattedRole(?Authenticatable $user): string
    {
        $role = static::getRole($user);
        if (!$role) {
            return 'Kullanıcı';
        }
        $labels = ['admin' => 'Yönetici', 'manager' => 'Yönetici', 'user' => 'Kullanıcı', 'moderator' => 'Moderatör', 'editor' => 'Editör'];
        return $labels[strtolower($role)] ?? ucfirst($role);
    }

    public static function can(?Authenticatable $user, string $permission): bool
    {
        $service = static::getService();
        return method_exists($service, 'hasPermission') ? $service->hasPermission($user, $permission) : false;
    }
}
