<?php

namespace Modules\AuthModule\Http\Controllers\Auth;

use Modules\AuthModule\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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

        $viewPath = config('auth-module.views.login', 'auth-module::auth.login');
        // Eğer view yoksa fallback kullan
        if (!view()->exists($viewPath)) {
            $viewPath = 'pages.auth.signin';
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
                ->withErrors(['email' => __('The email or password is incorrect.')])
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
     * Kullanıcı kaydı (User veya Company/Tenant)
     */
    public function register(Request $request)
    {
        $registerType = $request->get('register_type', 'user');

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:' . config('auth-module.validation.password_min_length', 6) . '|confirmed',
        ];
        $messages = [
            'name.required' => __('The name field is required.'),
            'email.required' => __('The email field is required.'),
            'email.email' => __('The email must be a valid email address.'),
            'password.required' => __('The password field is required.'),
            'password.min' => __('The password must be at least :min characters.', ['min' => config('auth-module.validation.password_min_length', 6)]),
            'password.confirmed' => __('The password confirmation does not match.'),
        ];

        if ($registerType === 'company') {
            $rules['company_name'] = 'required|string|max:255';
            $rules['company_email'] = 'nullable|email|max:255';
            $messages['company_name.required'] = __('The company name is required.');
        }

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->except('password', 'password_confirmation'));
        }

        $userModel = config('auth-module.multi_tenant.user_model', 'App\Models\User');
        $tenantId = null;

        // Company kaydı: önce Tenant oluştur
        if ($registerType === 'company') {
            $baseSlug = Str::slug($request->company_name);
            $slug = $baseSlug;
            $i = 1;
            while (Tenant::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $i++;
            }
            $tenant = Tenant::create([
                'name' => $request->company_name,
                'slug' => $slug,
                'email' => $request->filled('company_email') ? $request->company_email : $request->email,
                'is_active' => true,
                'is_trial' => true,
            ]);
            $tenantId = $tenant->id;
        }

        // Mevcut tenant bağlamında kayıt (multi-tenant açıksa ve company değilse)
        if ($tenantId === null && config('auth-module.multi_tenant.enabled', false)) {
            $tenantHelperClass = config('auth-module.multi_tenant.tenant_helper_class', 'App\Helpers\TenantHelper');
            if (class_exists($tenantHelperClass)) {
                $tenant = method_exists($tenantHelperClass, 'current')
                    ? $tenantHelperClass::current()
                    : null;

                if ($tenant) {
                    $tenantId = $tenant->id;

                    if (property_exists($tenant, 'max_users')) {
                        $userCount = $userModel::where('tenant_id', $tenantId)->count();
                        if ($userCount >= $tenant->max_users) {
                            return back()
                                ->withErrors(['email' => __('Maximum user limit reached for this tenant.')])
                                ->withInput($request->except('password', 'password_confirmation'));
                        }
                    }

                    $existingUser = $userModel::where('email', $request->email)
                        ->where('tenant_id', $tenantId)
                        ->first();

                    if ($existingUser) {
                        return back()
                            ->withErrors(['email' => __('This email address is already in use.')])
                            ->withInput($request->except('password', 'password_confirmation'));
                    }
                }
            }
        }

        // Email benzersizlik (tenant yoksa veya yeni tenant ise global kontrol)
        $existingQuery = $userModel::where('email', $request->email);
        if ($tenantId) {
            $existingQuery->where('tenant_id', $tenantId);
        } else {
            $existingQuery->whereNull('tenant_id');
        }
        if ($existingQuery->exists()) {
            return back()
                ->withErrors(['email' => __('This email address is already in use.')])
                ->withInput($request->except('password', 'password_confirmation'));
        }

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => true,
            'email_verified_at' => now(),
        ];
        if ($tenantId) {
            $userData['tenant_id'] = $tenantId;
        }

        $user = $userModel::create($userData);

        $roleToAssign = ($registerType === 'company')
            ? (config('role-permission-module.company_admin_role_slug', 'admin'))
            : config('role-permission-module.default_role_slug', 'user');

        if (class_exists(\Modules\RolePermissionModule\Services\RolePermissionService::class)) {
            try {
                app(\Modules\RolePermissionModule\Services\RolePermissionService::class)
                    ->assignRole($user, $roleToAssign, $tenantId);
            } catch (\Exception $e) {
                \Log::warning('Kayıt sonrası rol atama hatası: ' . $e->getMessage(), ['user_id' => $user->id]);
            }
        }

        $this->sendWelcomeNotification($user);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect(config('auth-module.redirects.after_register', '/dashboard'))
            ->with('success', __('Your account has been successfully created!'));
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
            ->with('success', __('You have successfully logged out.'));
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
