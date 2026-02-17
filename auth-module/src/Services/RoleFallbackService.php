<?php

namespace Modules\AuthModule\Services;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * role-permission-module yüklü değilse kullanılan fallback servis.
 * users.role kolonunu okur.
 */
class RoleFallbackService
{
    public function hasRole(?Authenticatable $user, string|array $roles): bool
    {
        $userRole = $this->getPrimaryRole($user);
        if (!$userRole) {
            return false;
        }
        $roles = is_array($roles) ? $roles : [$roles];
        return in_array(strtolower($userRole), array_map('strtolower', $roles));
    }

    public function getPrimaryRole(?Authenticatable $user): ?string
    {
        if (!$user) {
            return null;
        }
        return $user->role ?? null;
    }

    public function isAdmin(?Authenticatable $user): bool
    {
        return $this->hasRole($user, 'admin');
    }

    public function hasPermission(?Authenticatable $user, string $permission): bool
    {
        if (!$user) {
            return false;
        }
        $role = $this->getPrimaryRole($user);
        if ($role === 'admin') {
            return true;
        }
        $permissions = config('auth-module.permissions', []);
        return isset($permissions[$role]) && (in_array($permission, $permissions[$role]) || in_array('*', $permissions[$role]));
    }
}
