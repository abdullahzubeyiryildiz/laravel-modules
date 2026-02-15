<?php

namespace Modules\AuthModule\Services;

use Modules\AuthModule\Contracts\RoleServiceInterface;
use Illuminate\Contracts\Auth\Authenticatable;

class RoleService implements RoleServiceInterface
{
    /**
     * Kullanıcının rolünü kontrol et
     *
     * @param Authenticatable|null $user
     * @param string|array $roles
     * @return bool
     */
    public function hasRole(?Authenticatable $user, string|array $roles): bool
    {
        if (!$user) {
            return false;
        }

        $userRole = $this->getUserRole($user);

        if (is_array($roles)) {
            return in_array($userRole, $roles);
        }

        return $userRole === $roles;
    }

    /**
     * Kullanıcının rolünü al
     *
     * @param Authenticatable $user
     * @return string|null
     */
    public function getUserRole(Authenticatable $user): ?string
    {
        // User model'inde role attribute'u varsa (en yaygın kullanım)
        if (isset($user->role)) {
            return $user->role;
        }

        // User model'inde getRole() metodu varsa
        if (method_exists($user, 'getRole')) {
            return $user->getRole();
        }

        // User model'inde role() relationship'i varsa
        if (method_exists($user, 'role')) {
            try {
                $role = $user->role();
                if ($role) {
                    return is_object($role) ? ($role->name ?? $role->slug ?? null) : $role;
                }
            } catch (\Exception $e) {
                // Relationship yoksa veya hata varsa devam et
            }
        }

        // User model'inde toArray() ile kontrol et
        if (method_exists($user, 'toArray')) {
            $attributes = $user->toArray();
            if (isset($attributes['role'])) {
                return $attributes['role'];
            }
        }

        return null;
    }

    /**
     * Kullanıcı admin mi?
     *
     * @param Authenticatable|null $user
     * @return bool
     */
    public function isAdmin(?Authenticatable $user): bool
    {
        return $this->hasRole($user, 'admin');
    }

    /**
     * Kullanıcı manager mi?
     *
     * @param Authenticatable|null $user
     * @return bool
     */
    public function isManager(?Authenticatable $user): bool
    {
        return $this->hasRole($user, ['admin', 'manager']);
    }

    /**
     * Kullanıcı user mi?
     *
     * @param Authenticatable|null $user
     * @return bool
     */
    public function isUser(?Authenticatable $user): bool
    {
        return $this->hasRole($user, 'user');
    }

    /**
     * Kullanıcının rolünü formatla (görüntüleme için)
     *
     * @param Authenticatable|null $user
     * @return string
     */
    public function getFormattedRole(?Authenticatable $user): string
    {
        $role = $this->getUserRole($user);

        if (!$role) {
            return 'Kullanıcı';
        }

        $roleLabels = [
            'admin' => 'Yönetici',
            'manager' => 'Yönetici',
            'user' => 'Kullanıcı',
            'moderator' => 'Moderatör',
            'editor' => 'Editör',
        ];

        return $roleLabels[strtolower($role)] ?? ucfirst($role);
    }

    /**
     * Kullanıcının rolünü kontrol et (helper method)
     *
     * @param Authenticatable|null $user
     * @param string $role
     * @return bool
     */
    public function check(?Authenticatable $user, string $role): bool
    {
        return $this->hasRole($user, $role);
    }

    /**
     * Tüm rolleri listele
     *
     * @return array
     */
    public function getAllRoles(): array
    {
        return [
            'admin' => 'Yönetici',
            'manager' => 'Yönetici',
            'moderator' => 'Moderatör',
            'editor' => 'Editör',
            'user' => 'Kullanıcı',
        ];
    }

    /**
     * Rol yetkilerini kontrol et
     *
     * @param Authenticatable|null $user
     * @param string $permission
     * @return bool
     */
    public function can(?Authenticatable $user, string $permission): bool
    {
        if (!$user) {
            return false;
        }

        $role = $this->getUserRole($user);

        // Admin her şeyi yapabilir
        if ($role === 'admin') {
            return true;
        }

        // Rol bazlı yetki kontrolü
        $permissions = config('auth-module.permissions', []);

        if (isset($permissions[$role]) && in_array($permission, $permissions[$role])) {
            return true;
        }

        // User model'inde can() metodu varsa
        if (method_exists($user, 'can')) {
            return $user->can($permission);
        }

        return false;
    }
}
