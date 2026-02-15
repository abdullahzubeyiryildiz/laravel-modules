<?php

namespace Modules\AuthModule\Http\Controllers\Auth\Social;

use Modules\AuthModule\Http\Controllers\Controller;
use Modules\AuthModule\Services\SocialAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    protected $socialAuthService;

    public function __construct(SocialAuthService $socialAuthService)
    {
        $this->socialAuthService = $socialAuthService;
    }

    /**
     * Social provider'a yönlendir
     */
    public function redirect(string $provider)
    {
        // Provider aktif mi kontrol et
        if (!$this->isProviderEnabled($provider)) {
            abort(404, 'Bu giriş yöntemi aktif değil.');
        }

        // Socialite yüklü mü kontrol et
        if (!class_exists('Laravel\Socialite\Facades\Socialite')) {
            abort(500, 'Laravel Socialite paketi yüklü değil. Lütfen "composer require laravel/socialite" komutunu çalıştırın.');
        }

        try {
            return Socialite::driver($provider)->redirect();
        } catch (\Exception $e) {
            return redirect()->route('login')
                ->with('error', 'Giriş yapılamadı: ' . $e->getMessage());
        }
    }

    /**
     * Social provider'dan dönüş
     */
    public function callback(string $provider, Request $request)
    {
        // Provider aktif mi kontrol et
        if (!$this->isProviderEnabled($provider)) {
            abort(404, 'Bu giriş yöntemi aktif değil.');
        }

        // Socialite yüklü mü kontrol et
        if (!class_exists('Laravel\Socialite\Facades\Socialite')) {
            abort(500, 'Laravel Socialite paketi yüklü değil.');
        }

        try {
            // OAuth hatası kontrolü
            if ($request->has('error')) {
                return redirect()->route('login')
                    ->with('error', 'Giriş iptal edildi veya bir hata oluştu.');
            }

            // Provider'dan kullanıcı bilgilerini al
            $providerUser = Socialite::driver($provider)->user();

            // Kullanıcıyı bul veya oluştur
            $user = $this->socialAuthService->findOrCreateUser($provider, $providerUser);

            // Giriş yap
            Auth::login($user, true); // Remember me

            // Yönlendir
            return redirect(config('auth-module.redirects.after_login', '/dashboard'))
                ->with('success', 'Başarıyla giriş yaptınız!');
        } catch (\Exception $e) {
            \Log::error('Social login hatası: ' . $e->getMessage());
            return redirect()->route('login')
                ->with('error', 'Giriş yapılırken bir hata oluştu.');
        }
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
