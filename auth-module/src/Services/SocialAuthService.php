<?php

namespace Modules\AuthModule\Services;

use Modules\AuthModule\Contracts\SocialAuthServiceInterface;
use Modules\AuthModule\Models\SocialAccount;
use Modules\AuthModule\Exceptions\SocialAuthException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SocialAuthService implements SocialAuthServiceInterface
{
    /**
     * Social provider'dan gelen kullanıcıyı bul veya oluştur
     */
    public function findOrCreateUser(string $provider, object $providerUser): object
    {
        $userModel = config('auth-module.multi_tenant.user_model', 'App\Models\User');
        
        // Social account'u bul
        $socialAccount = SocialAccount::where('provider', $provider)
            ->where('provider_id', $providerUser->getId())
            ->first();

        if ($socialAccount) {
            // Mevcut kullanıcıyı döndür
            return $socialAccount->user;
        }

        // Email ile kullanıcıyı bul
        if ($providerUser->getEmail()) {
            $user = $userModel::where('email', $providerUser->getEmail())->first();
            
            if ($user) {
                // Mevcut kullanıcıya social account ekle
                $this->createSocialAccount($user, $provider, $providerUser);
                return $user;
            }
        }

        // Yeni kullanıcı oluştur
        $user = $this->createUserFromProvider($providerUser);
        
        // Social account oluştur
        $this->createSocialAccount($user, $provider, $providerUser);

        return $user;
    }

    /**
     * Provider'dan kullanıcı oluştur
     */
    protected function createUserFromProvider(object $providerUser): object
    {
        $userModel = config('auth-module.multi_tenant.user_model', 'App\Models\User');
        
        $tenantId = null;
        if (config('auth-module.multi_tenant.enabled', false)) {
            $tenantHelperClass = config('auth-module.multi_tenant.tenant_helper_class', 'App\Helpers\TenantHelper');
            if (class_exists($tenantHelperClass) && method_exists($tenantHelperClass, 'id')) {
                $tenantId = $tenantHelperClass::id();
            }
        }

        $userData = [
            'name' => $providerUser->getName() ?? $providerUser->getNickname() ?? 'User',
            'email' => $providerUser->getEmail(),
            'password' => Hash::make(Str::random(32)), // Rastgele şifre
            'email_verified_at' => now(), // Social login'de email zaten doğrulanmış sayılır
        ];

        // Tenant ID ekle
        if ($tenantId && property_exists($userModel, 'tenant_id')) {
            $userData['tenant_id'] = $tenantId;
        }

        // Role ekle
        if (property_exists($userModel, 'role')) {
            $userData['role'] = config('auth-module.roles.default', 'user');
        }

        // is_active ekle
        if (property_exists($userModel, 'is_active')) {
            $userData['is_active'] = true;
        }

        // Avatar ekle (eğer varsa)
        if ($providerUser->getAvatar() && property_exists($userModel, 'avatar')) {
            $userData['avatar'] = $providerUser->getAvatar();
        }

        return $userModel::create($userData);
    }

    /**
     * Social account oluştur
     */
    protected function createSocialAccount(object $user, string $provider, object $providerUser): SocialAccount
    {
        return SocialAccount::create([
            'user_id' => $user->id,
            'provider' => $provider,
            'provider_id' => $providerUser->getId(),
            'email' => $providerUser->getEmail(),
            'name' => $providerUser->getName() ?? $providerUser->getNickname(),
            'avatar' => $providerUser->getAvatar(),
            'provider_data' => [
                'nickname' => $providerUser->getNickname(),
                'raw' => $providerUser->user ?? null,
            ],
        ]);
    }

    /**
     * Kullanıcının social account'larını al
     */
    public function getUserSocialAccounts(object $user): \Illuminate\Database\Eloquent\Collection
    {
        return SocialAccount::where('user_id', $user->id)->get();
    }

    /**
     * Social account'u sil
     */
    public function disconnectSocialAccount(object $user, string $provider): bool
    {
        $socialAccount = SocialAccount::where('user_id', $user->id)
            ->where('provider', $provider)
            ->first();

        if ($socialAccount) {
            return $socialAccount->delete();
        }

        return false;
    }
}
