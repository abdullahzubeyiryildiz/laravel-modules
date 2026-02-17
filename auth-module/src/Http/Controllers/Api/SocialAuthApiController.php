<?php

namespace Modules\AuthModule\Http\Controllers\Api;

use Modules\AuthModule\Http\Controllers\Controller;
use Modules\AuthModule\Services\SocialAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthApiController extends Controller
{
    protected $socialAuthService;

    public function __construct(SocialAuthService $socialAuthService)
    {
        $this->socialAuthService = $socialAuthService;
    }


    /**
     * Social provider'a yönlendir (URL döndür)
     */
    public function redirect(string $provider)
    {
        // Provider aktif mi kontrol et
        if (!$this->isProviderEnabled($provider)) {
            return $this->response(
                null,
                __('This login method is not enabled.'),
                404
            );
        }

        // Socialite yüklü mü kontrol et
        if (!class_exists('Laravel\Socialite\Facades\Socialite')) {
            return $this->response(
                null,
                __('Laravel Socialite package is not installed. Please run "composer require laravel/socialite".'),
                500
            );
        }

        try {
            $url = Socialite::driver($provider)->stateless()->redirect()->getTargetUrl();

            return $this->response([
                'redirect_url' => $url,
            ], __('Redirect URL created.'), 200);
        } catch (\Exception $e) {
            return $this->response(
                null,
                __('Failed to create redirect URL: :error', ['error' => $e->getMessage()]),
                500
            );
        }
    }

    /**
     * Social provider'dan dönüş (token ile)
     */
    public function callback(string $provider, Request $request)
    {
        // Provider aktif mi kontrol et
        if (!$this->isProviderEnabled($provider)) {
            return $this->response(
                null,
                __('This login method is not enabled.'),
                404
            );
        }

        try {
            // OAuth hatası kontrolü
            if ($request->has('error')) {
                return $this->response(
                    null,
                    __('Login was cancelled or an error occurred.'),
                    400
                );
            }

            // Socialite yüklü mü kontrol et
            if (!class_exists('Laravel\Socialite\Facades\Socialite')) {
                return $this->response(
                    null,
                    __('Laravel Socialite package is not installed.'),
                    500
                );
            }

            // Provider'dan kullanıcı bilgilerini al
            $providerUser = Socialite::driver($provider)->stateless()->user();

            // Kullanıcıyı bul veya oluştur
            $user = $this->socialAuthService->findOrCreateUser($provider, $providerUser);

            // Sanctum token oluştur
            $token = $user->createToken('social-login')->plainTextToken;

            return $this->response([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => class_exists(\Modules\AuthModule\Helpers\RoleHelper::class) ? \Modules\AuthModule\Helpers\RoleHelper::getRole($user) : ($user->role ?? null),
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ], __('You have successfully logged in!'), 200);
        } catch (\Exception $e) {
            \Log::error('Social login hatası: ' . $e->getMessage());
            return $this->response(
                null,
                __('An error occurred during login: :error', ['error' => $e->getMessage()]),
                500
            );
        }
    }

    /**
     * Bağlı social account'ları listele
     */
    public function accounts(Request $request)
    {
        $user = $request->user();
        $accounts = $this->socialAuthService->getUserSocialAccounts($user);

        return $this->response([
            'accounts' => $accounts->map(function ($account) {
                return [
                    'id' => $account->id,
                    'provider' => $account->provider,
                    'email' => $account->email,
                    'name' => $account->name,
                    'avatar' => $account->avatar,
                    'connected_at' => $account->created_at,
                ];
            }),
        ], __('Connected accounts listed.'), 200);
    }

    /**
     * Social account'u bağlantıyı kes
     */
    public function disconnect(Request $request, string $provider)
    {
        $user = $request->user();

        // En az bir şifre veya başka bir social account olmalı
        $socialAccounts = $this->socialAuthService->getUserSocialAccounts($user);
        $hasPassword = !empty($user->password) && $user->password !== '';

        if ($socialAccounts->count() <= 1 && !$hasPassword) {
            return $this->response(
                null,
                __('At least one login method is required. Set a password or connect another account.'),
                400
            );
        }

        $disconnected = $this->socialAuthService->disconnectSocialAccount($user, $provider);

        if ($disconnected) {
            return $this->response(
                null,
                __('Account disconnected.'),
                200
            );
        }

        return $this->response(
            null,
            __('Account not found.'),
            404
        );
    }

    /**
     * Provider aktif mi kontrol et
     */
    protected function isProviderEnabled(string $provider): bool
    {
        $providers = config('auth-module.social.providers', []);
        return isset($providers[$provider]) && $providers[$provider]['enabled'] === true;
    }
}
