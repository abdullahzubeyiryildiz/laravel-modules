<?php

namespace Modules\AuthModule\Contracts;

interface SocialAuthServiceInterface
{
    /**
     * Social provider'dan gelen kullanıcıyı bul veya oluştur
     */
    public function findOrCreateUser(string $provider, object $providerUser): object;

    /**
     * Kullanıcının social account'larını al
     */
    public function getUserSocialAccounts(object $user): \Illuminate\Database\Eloquent\Collection;

    /**
     * Social account'u sil
     */
    public function disconnectSocialAccount(object $user, string $provider): bool;
}
