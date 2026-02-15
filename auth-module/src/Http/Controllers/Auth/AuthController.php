<?php

namespace Modules\AuthModule\Http\Controllers\Auth;

use Modules\AuthModule\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Giriş sayfasını göster
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect(config('auth-module.redirects.after_login', '/dashboard'));
        }

        $viewPath = config('auth-module.views.login', 'pages.auth.signin');
        // Eğer auth-module namespace'i kullanılıyorsa ve yüklü değilse, fallback kullan
        if (str_starts_with($viewPath, 'auth-module::')) {
            try {
                if (!view()->exists($viewPath)) {
                    $viewPath = 'pages.auth.signin';
                }
            } catch (\Exception $e) {
                $viewPath = 'pages.auth.signin';
            }
        }
        return view($viewPath, [
            'title' => 'Giriş Yap'
        ]);
    }

    /**
     * Kullanıcı girişi
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:' . config('auth-module.validation.password_min_length', 6),
        ], [
            'email.required' => 'E-posta adresi gereklidir.',
            'email.email' => 'Geçerli bir e-posta adresi giriniz.',
            'password.required' => 'Şifre gereklidir.',
            'password.min' => 'Şifre en az ' . config('auth-module.validation.password_min_length', 6) . ' karakter olmalıdır.',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->only('email'));
        }

        $userModel = config('auth-module.multi_tenant.user_model', 'App\Models\User');
        $user = $userModel::where('email', $request->email)
            ->where('is_active', true);

        // Multi-tenant desteği (opsiyonel)
        if (config('auth-module.multi_tenant.enabled', false)) {
            $tenantHelperClass = config('auth-module.multi_tenant.tenant_helper_class', 'App\Helpers\TenantHelper');
            if (class_exists($tenantHelperClass) && method_exists($tenantHelperClass, 'id')) {
                $tenantId = $tenantHelperClass::id();
                if ($tenantId) {
                    $user->where('tenant_id', $tenantId);
                }
            }
        }

        $user = $user->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()
                ->withErrors(['email' => 'E-posta veya şifre hatalı.'])
                ->withInput($request->only('email'));
        }

        Auth::login($user, $request->filled('remember'));
        $request->session()->regenerate();

        return redirect()->intended(config('auth-module.redirects.after_login', '/dashboard'));
    }

    /**
     * Kayıt sayfasını göster
     */
    public function showRegisterForm()
    {
        if (Auth::check()) {
            return redirect(config('auth-module.redirects.after_register', '/dashboard'));
        }

        $viewPath = config('auth-module.views.register', 'pages.auth.signup');
        // Eğer auth-module namespace'i kullanılıyorsa ve yüklü değilse, fallback kullan
        if (str_starts_with($viewPath, 'auth-module::')) {
            try {
                if (!view()->exists($viewPath)) {
                    $viewPath = 'pages.auth.signup';
                }
            } catch (\Exception $e) {
                $viewPath = 'pages.auth.signup';
            }
        }
        return view($viewPath, [
            'title' => 'Kayıt Ol'
        ]);
    }

    /**
     * Kullanıcı kaydı
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:' . config('auth-module.validation.password_min_length', 6) . '|confirmed',
        ], [
            'name.required' => 'Ad soyad gereklidir.',
            'email.required' => 'E-posta adresi gereklidir.',
            'email.email' => 'Geçerli bir e-posta adresi giriniz.',
            'password.required' => 'Şifre gereklidir.',
            'password.min' => 'Şifre en az ' . config('auth-module.validation.password_min_length', 6) . ' karakter olmalıdır.',
            'password.confirmed' => 'Şifreler eşleşmiyor.',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->except('password', 'password_confirmation'));
        }

        $userModel = config('auth-module.multi_tenant.user_model', 'App\Models\User');
        $tenantId = null;

        // Multi-tenant desteği (opsiyonel)
        if (config('auth-module.multi_tenant.enabled', false)) {
            $tenantHelperClass = config('auth-module.multi_tenant.tenant_helper_class', 'App\Helpers\TenantHelper');
            if (class_exists($tenantHelperClass)) {
                $tenant = method_exists($tenantHelperClass, 'current')
                    ? $tenantHelperClass::current()
                    : null;

                if ($tenant) {
                    $tenantId = $tenant->id;

                    // Maksimum kullanıcı kontrolü
                    if (property_exists($tenant, 'max_users')) {
                        $userCount = $userModel::where('tenant_id', $tenantId)->count();
                        if ($userCount >= $tenant->max_users) {
                            return back()
                                ->withErrors(['email' => 'Bu tenant için maksimum kullanıcı sayısına ulaşıldı.'])
                                ->withInput($request->except('password', 'password_confirmation'));
                        }
                    }

                    // Email unique kontrolü (tenant bazlı)
                    $existingUser = $userModel::where('email', $request->email)
                        ->where('tenant_id', $tenantId)
                        ->first();

                    if ($existingUser) {
                        return back()
                            ->withErrors(['email' => 'Bu e-posta adresi zaten kullanılıyor.'])
                            ->withInput($request->except('password', 'password_confirmation'));
                    }
                }
                // Tenant yoksa normal devam et (multi-tenant kapalı gibi davran)
            }
        }

        // Multi-tenant olmadan email kontrolü
        if (!isset($tenantId)) {
            $existingUser = $userModel::where('email', $request->email)->first();
            if ($existingUser) {
                return back()
                    ->withErrors(['email' => 'Bu e-posta adresi zaten kullanılıyor.'])
                    ->withInput($request->except('password', 'password_confirmation'));
            }
        }

        // Kullanıcı oluştur
        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user',
            'is_active' => true,
            'email_verified_at' => now(),
        ];

        if ($tenantId) {
            $userData['tenant_id'] = $tenantId;
        }

        $user = $userModel::create($userData);

        // Notification gönder (mail ve/veya SMS)
        $this->sendWelcomeNotification($user);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect(config('auth-module.redirects.after_register', '/dashboard'))
            ->with('success', 'Hesabınız başarıyla oluşturuldu!');
    }

    /**
     * Kullanıcı çıkışı
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect(config('auth-module.redirects.after_logout', '/login'))
            ->with('success', 'Başarıyla çıkış yaptınız.');
    }

    /**
     * Hoş geldin bildirimi gönder
     */
    protected function sendWelcomeNotification($user): void
    {
        if (!class_exists(NotificationService::class)) {
            return; // Notification modülü yüklü değilse atla
        }

        try {
            $notificationService = app(NotificationService::class);
            $templates = config('notification-module.templates.welcome', []);

            // Mail gönder
            if (config('notification-module.mail.enabled', true) && isset($templates['mail'])) {
                $mailSubject = str_replace(':name', $user->name, $templates['mail']['subject']);
                $mailBody = str_replace(':name', $user->name, $templates['mail']['body']);
                $notificationService->sendMail($user->email, $mailSubject, $mailBody);
            }

            // SMS gönder (telefon numarası varsa)
            if (config('notification-module.sms.enabled', true) && isset($templates['sms']) && isset($user->phone)) {
                $smsMessage = str_replace(':name', $user->name, $templates['sms']);
                $notificationService->sendSms($user->phone, $smsMessage);
            }
        } catch (\Exception $e) {
            // Notification hatası kayıt işlemini engellemez
            \Log::error('Welcome notification gönderme hatası: ' . $e->getMessage());
        }
    }
}
