<?php

namespace Modules\AuthModule\Http\Controllers\Auth;

use Modules\AuthModule\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    /**
     * Şifre sıfırlama isteği sayfasını göster
     */
    public function showLinkRequestForm()
    {
        $viewPath = config('auth-module.views.password.request', 'auth-module::auth.passwords.email');
        // Eğer auth-module namespace'i yüklü değilse, fallback view kullan
        if (str_starts_with($viewPath, 'auth-module::') && !view()->exists($viewPath)) {
            $viewPath = 'pages.auth.forgot-password'; // Varsayılan view path
        }
        return view($viewPath, [
            'title' => __('Forgot Password')
        ]);
    }

    /**
     * Şifre sıfırlama linki gönder
     */
    public function sendResetLinkEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ], [
            'email.required' => __('The email field is required.'),
            'email.email' => __('The email must be a valid email address.'),
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->only('email'));
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
            // Güvenlik için kullanıcı bulunamadığında da başarı mesajı göster
            return back()->with('status', __('If this email address is registered, a password reset link has been sent.'));
        }

        // Password reset token oluştur
        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        // Email gönder (basit versiyon - gerçek projede Mail facade kullanılmalı)
        $resetUrl = url(config('auth-module.routes.prefix', '') . '/password/reset/' . $token . '?email=' . urlencode($request->email));

        // Burada email gönderim işlemi yapılabilir
        // Mail::to($user->email)->send(new PasswordResetMail($resetUrl));

        return back()->with('status', 'Eğer bu e-posta adresi kayıtlıysa, şifre sıfırlama linki gönderildi.');
    }

    /**
     * Şifre sıfırlama formunu göster
     */
    public function showResetForm(Request $request, $token = null)
    {
        $viewPath = config('auth-module.views.password.reset', 'auth-module::auth.passwords.reset');
        // Eğer auth-module namespace'i yüklü değilse, fallback view kullan
        if (str_starts_with($viewPath, 'auth-module::') && !view()->exists($viewPath)) {
            $viewPath = 'pages.auth.reset-password'; // Varsayılan view path
        }
        return view($viewPath, [
            'title' => __('Reset Password'),
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    /**
     * Şifreyi sıfırla
     */
    public function reset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:' . config('auth-module.validation.password_min_length', 6) . '|confirmed',
        ], [
            'token.required' => __('The token field is required.'),
            'email.required' => __('The email field is required.'),
            'email.email' => __('The email must be a valid email address.'),
            'password.required' => __('The password field is required.'),
            'password.min' => __('The password must be at least :min characters.', ['min' => config('auth-module.validation.password_min_length', 6)]),
            'password.confirmed' => __('The password confirmation does not match.'),
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->only('email'));
        }

        // Token kontrolü
        $passwordReset = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$passwordReset || !Hash::check($request->token, $passwordReset->token)) {
            return back()
                ->withErrors(['email' => __('Invalid or expired token.')])
                ->withInput($request->only('email'));
        }

        // Token süresi kontrolü (60 dakika)
        if (now()->diffInMinutes($passwordReset->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return back()
                ->withErrors(['email' => __('Token has expired. Please request a new password reset.')])
                ->withInput($request->only('email'));
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
            return back()
                ->withErrors(['email' => __('User not found.')])
                ->withInput($request->only('email'));
        }

        // Şifreyi güncelle
        $user->password = Hash::make($request->password);
        $user->save();

        // Token'ı sil
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return redirect()->route('login')
            ->with('success', __('Your password has been successfully reset. You can now log in.'));
    }
}
