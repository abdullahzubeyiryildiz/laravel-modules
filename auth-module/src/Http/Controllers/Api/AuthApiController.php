<?php

namespace Modules\AuthModule\Http\Controllers\Api;

use Modules\AuthModule\Http\Controllers\Controller;
use Modules\NotificationModule\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuthApiController extends Controller
{

    /**
     * Kullanıcı girişi (API)
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:' . config('auth-module.validation.password_min_length', 6),
        ]);

        if ($validator->fails()) {
            return $this->response(
                null,
                __('Validation failed'),
                422,
                $validator->errors()
            );
        }

        $userModel = config('auth-module.multi_tenant.user_model', 'App\Models\User');
        $user = $userModel::where('email', $request->email)
            ->where('is_active', true);

        // Multi-tenant desteği
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
            return $this->response(
                null,
                __('The email or password is incorrect.'),
                401
            );
        }

        // Token oluştur (Sanctum)
        if (method_exists($user, 'createToken')) {
            $token = $user->createToken('auth-token')->plainTextToken;
        } else {
            // Sanctum yüklü değilse manuel token oluştur
            $token = Str::random(80);
            // Token'ı veritabanına kaydet (custom implementation gerekebilir)
        }

        return $this->response([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'token' => $token,
            'token_type' => 'Bearer',
        ], __('Login successful'), 200);
    }

    /**
     * Kullanıcı kaydı (API)
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:' . config('auth-module.validation.password_min_length', 6) . '|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->response(
                null,
                __('Validation failed'),
                422,
                $validator->errors()
            );
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
                            return $this->response(
                                null,
                                __('Maximum user limit reached for this tenant.'),
                                403
                            );
                        }
                    }

                    // Email unique kontrolü (tenant bazlı)
                    $existingUser = $userModel::where('email', $request->email)
                        ->where('tenant_id', $tenantId)
                        ->first();

                    if ($existingUser) {
                        return $this->response(
                            null,
                            __('This email address is already in use.'),
                            409,
                            ['email' => [__('This email address is already in use.')]]
                        );
                    }
                }
                // Tenant yoksa normal devam et (multi-tenant kapalı gibi davran)
            }
        }

        // Multi-tenant olmadan email kontrolü
        if (!isset($tenantId)) {
            $existingUser = $userModel::where('email', $request->email)->first();
            if ($existingUser) {
                return $this->response(
                    null,
                    __('This email address is already in use.'),
                    409,
                    ['email' => [__('This email address is already in use.')]]
                );
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

        // Token oluştur (Sanctum)
        if (method_exists($user, 'createToken')) {
            $token = $user->createToken('auth-token')->plainTextToken;
        } else {
            $token = Str::random(80);
        }

        return $this->response([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'token' => $token,
            'token_type' => 'Bearer',
        ], __('Registration successful'), 201);
    }

    /**
     * Kullanıcı çıkışı (API)
     */
    public function logout(Request $request)
    {
        // Mevcut token'ı sil (Sanctum)
        if (method_exists($request->user(), 'currentAccessToken')) {
            $request->user()->currentAccessToken()->delete();
        }

        return $this->response(
            null,
            __('You have successfully logged out.'),
            200
        );
    }

    /**
     * Mevcut kullanıcı bilgileri (API)
     */
    public function me(Request $request)
    {
        return $this->response([
            'user' => [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'email' => $request->user()->email,
                'role' => $request->user()->role,
            ],
        ], __('User information'), 200);
    }

    /**
     * Şifre sıfırlama isteği (API)
     */
    public function passwordRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return $this->response(
                null,
                __('Validation failed'),
                422,
                $validator->errors()
            );
        }

        $userModel = config('auth-module.multi_tenant.user_model', 'App\Models\User');
        $user = $userModel::where('email', $request->email);

        // Multi-tenant desteği
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

        if (!$user) {
            // Güvenlik için kullanıcı bulunamadığında da başarı mesajı döndür
            return $this->response(
                null,
                __('If this email address is registered, a password reset link has been sent.'),
                200
            );
        }

        // Token oluştur
        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        // Reset URL
        $resetUrl = url(config('auth-module.routes.prefix', '') . '/password/reset/' . $token . '?email=' . urlencode($request->email));

        // Burada email gönderim işlemi yapılabilir
        // Mail::to($user->email)->send(new PasswordResetMail($resetUrl));

        return $this->response([
            'reset_url' => $resetUrl,
            'token' => $token, // Development için - production'da kaldırılmalı
        ], __('If this email address is registered, a password reset link has been sent.'), 200);
    }

    /**
     * Şifre sıfırlama (API)
     */
    public function passwordReset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:' . config('auth-module.validation.password_min_length', 6) . '|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->response(
                null,
                __('Validation failed'),
                422,
                $validator->errors()
            );
        }

        // Token kontrolü
        $passwordReset = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$passwordReset || !Hash::check($request->token, $passwordReset->token)) {
            return $this->response(
                null,
                __('Invalid or expired token.'),
                400
            );
        }

        // Token süresi kontrolü (60 dakika)
        if (now()->diffInMinutes($passwordReset->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return $this->response(
                null,
                __('Token has expired. Please request a new password reset.'),
                400
            );
        }

        // Kullanıcıyı bul ve şifreyi güncelle
        $userModel = config('auth-module.multi_tenant.user_model', 'App\Models\User');
        $user = $userModel::where('email', $request->email);

        // Multi-tenant desteği
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

        if (!$user) {
            return $this->response(
                null,
                __('User not found.'),
                404
            );
        }

        // Şifreyi güncelle
        $user->password = Hash::make($request->password);
        $user->save();

        // Token'ı sil
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return $this->response(
            null,
            __('Your password has been successfully reset. You can now log in.'),
            200
        );
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
