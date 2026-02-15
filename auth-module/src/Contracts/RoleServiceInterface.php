<?php

namespace Modules\AuthModule\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface RoleServiceInterface
{
    /**
     * Kullanıcının rolünü kontrol et
     */
    public function hasRole(?Authenticatable $user, string|array $roles): bool;

    /**
     * Kullanıcının rolünü al
     */
    public function getUserRole(Authenticatable $user): ?string;

    /**
     * Kullanıcı admin mi?
     */
    public function isAdmin(?Authenticatable $user): bool;

    /**
     * Kullanıcı manager mi?
     */
    public function isManager(?Authenticatable $user): bool;

    /**
     * Kullanıcı user mi?
     */
    public function isUser(?Authenticatable $user): bool;

    /**
     * Kullanıcının rolünü formatla
     */
    public function getFormattedRole(?Authenticatable $user): string;

    /**
     * Rol yetkilerini kontrol et
     */
    public function can(?Authenticatable $user, string $permission): bool;

    /**
     * Tüm rolleri listele
     */
    public function getAllRoles(): array;
}
