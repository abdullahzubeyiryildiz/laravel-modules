<?php

namespace Modules\AuthModule\Traits;

use Modules\AuthModule\Models\SocialAccount;

trait HasSocialAccounts
{
    /**
     * Kullanıcının social account'ları
     */
    public function socialAccounts()
    {
        return $this->hasMany(SocialAccount::class);
    }

    /**
     * Belirli bir provider'a bağlı mı?
     */
    public function hasSocialAccount(string $provider): bool
    {
        return $this->socialAccounts()
            ->where('provider', $provider)
            ->exists();
    }

    /**
     * Social account ekle
     */
    public function addSocialAccount(string $provider, string $providerId, array $data = []): SocialAccount
    {
        return $this->socialAccounts()->create([
            'provider' => $provider,
            'provider_id' => $providerId,
            'email' => $data['email'] ?? $this->email,
            'name' => $data['name'] ?? $this->name,
            'avatar' => $data['avatar'] ?? null,
            'provider_data' => $data['provider_data'] ?? null,
        ]);
    }

    /**
     * Social account'u kaldır
     */
    public function removeSocialAccount(string $provider): bool
    {
        return $this->socialAccounts()
            ->where('provider', $provider)
            ->delete() > 0;
    }

    /**
     * Tüm social account'ları kaldır
     */
    public function removeAllSocialAccounts(): bool
    {
        return $this->socialAccounts()->delete() > 0;
    }
}
