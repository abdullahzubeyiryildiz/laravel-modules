<?php

namespace Modules\AuthModule\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Modules\AuthModule\Services\RoleService;
use Modules\AuthModule\Services\SocialAuthService;

class AuthModuleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/auth-module.php',
            'auth-module'
        );

        // Interface binding'leri
        $this->app->singleton(
            \Modules\AuthModule\Contracts\RoleServiceInterface::class,
            RoleService::class
        );

        $this->app->singleton(
            \Modules\AuthModule\Contracts\SocialAuthServiceInterface::class,
            SocialAuthService::class
        );

        // Service'leri singleton olarak kaydet
        $this->app->singleton(RoleService::class, function ($app) {
            return new RoleService();
        });

        $this->app->singleton(SocialAuthService::class, function ($app) {
            return new SocialAuthService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/../../config/auth-module.php' => config_path('auth-module.php'),
        ], 'auth-module-config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../../database/migrations' => database_path('migrations'),
        ], 'auth-module-migrations');

        // Publish views
        $this->publishes([
            __DIR__ . '/../../resources/views' => resource_path('views/vendor/auth-module'),
        ], 'auth-module-views');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'auth-module');

        // Social auth config'i services.php'ye merge et
        $this->configureSocialProviders();

        // Load routes
        if (config('auth-module.routes.enabled', true)) {
            $this->loadRoutes();
        }

        // Load API routes
        if (config('auth-module.api.enabled', true)) {
            $this->loadApiRoutes();
        }

        // Social Auth Routes
        if (config('auth-module.social.enabled', false)) {
            $this->loadSocialRoutes();
        }

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Modules\AuthModule\Commands\InstallAuthModuleCommand::class,
            ]);
        }

        // Otomatik olarak User model'ine trait'leri ekle (sadece ilk kurulumda)
        if ($this->app->runningInConsole() && !$this->app->runningUnitTests()) {
            $this->autoInstallTraits();
        }
    }

    /**
     * Social provider'ları services.php'ye ekle
     */
    protected function configureSocialProviders(): void
    {
        if (!config('auth-module.social.enabled', false)) {
            return;
        }

        $providers = config('auth-module.social.providers', []);

        foreach ($providers as $provider => $config) {
            if ($config['enabled'] ?? false) {
                config([
                    "services.{$provider}" => [
                        'client_id' => $config['client_id'] ?? null,
                        'client_secret' => $config['client_secret'] ?? null,
                        'redirect' => $config['redirect'] ?? "/auth/{$provider}/callback",
                    ],
                ]);
            }
        }
    }

    /**
     * Load routes
     */
    protected function loadRoutes(): void
    {
        Route::middleware(config('auth-module.routes.middleware.guest', ['web', 'guest']))
            ->prefix(config('auth-module.routes.prefix', ''))
            ->group(function () {
                Route::get(
                    config('auth-module.routes.login', 'login'),
                    [\Modules\AuthModule\Http\Controllers\Auth\AuthController::class, 'showLoginForm']
                )->name('login');

                Route::post(
                    config('auth-module.routes.login', 'login'),
                    [\Modules\AuthModule\Http\Controllers\Auth\AuthController::class, 'login']
                );

                Route::get(
                    config('auth-module.routes.register', 'register'),
                    [\Modules\AuthModule\Http\Controllers\Auth\AuthController::class, 'showRegisterForm']
                )->name('register');

                Route::post(
                    config('auth-module.routes.register', 'register'),
                    [\Modules\AuthModule\Http\Controllers\Auth\AuthController::class, 'register']
                );

                // Password Reset Routes
                Route::get(
                    config('auth-module.routes.password.request', 'password/reset'),
                    [\Modules\AuthModule\Http\Controllers\Auth\PasswordResetController::class, 'showLinkRequestForm']
                )->name('password.request');

                Route::post(
                    config('auth-module.routes.password.email', 'password/email'),
                    [\Modules\AuthModule\Http\Controllers\Auth\PasswordResetController::class, 'sendResetLinkEmail']
                )->name('password.email');

                Route::get(
                    config('auth-module.routes.password.reset', 'password/reset/{token}'),
                    [\Modules\AuthModule\Http\Controllers\Auth\PasswordResetController::class, 'showResetForm']
                )->name('password.reset');

                Route::post(
                    config('auth-module.routes.password.update', 'password/reset'),
                    [\Modules\AuthModule\Http\Controllers\Auth\PasswordResetController::class, 'reset']
                )->name('password.update');
            });

        Route::middleware(config('auth-module.routes.middleware.auth', ['web', 'auth']))
            ->prefix(config('auth-module.routes.prefix', ''))
            ->group(function () {
                Route::post(
                    config('auth-module.routes.logout', 'logout'),
                    [\Modules\AuthModule\Http\Controllers\Auth\AuthController::class, 'logout']
                )->name('logout');
            });
    }

    /**
     * Load API routes
     */
    protected function loadApiRoutes(): void
    {
        Route::middleware(config('auth-module.api.middleware.guest', ['api']))
            ->prefix(config('auth-module.api.prefix', 'api/auth'))
            ->group(function () {
                Route::post(
                    'login',
                    [\Modules\AuthModule\Http\Controllers\Api\AuthApiController::class, 'login']
                )->name('api.login');

                Route::post(
                    'register',
                    [\Modules\AuthModule\Http\Controllers\Api\AuthApiController::class, 'register']
                )->name('api.register');

                Route::post(
                    'password/request',
                    [\Modules\AuthModule\Http\Controllers\Api\AuthApiController::class, 'passwordRequest']
                )->name('api.password.request');

                Route::post(
                    'password/reset',
                    [\Modules\AuthModule\Http\Controllers\Api\AuthApiController::class, 'passwordReset']
                )->name('api.password.reset');
            });

        Route::middleware(config('auth-module.api.middleware.auth', ['api', 'auth:sanctum']))
            ->prefix(config('auth-module.api.prefix', 'api/auth'))
            ->group(function () {
                Route::post(
                    'logout',
                    [\Modules\AuthModule\Http\Controllers\Api\AuthApiController::class, 'logout']
                )->name('api.logout');

                Route::get(
                    'me',
                    [\Modules\AuthModule\Http\Controllers\Api\AuthApiController::class, 'me']
                )->name('api.me');

                // Profile routes
                Route::prefix('profile')->name('profile.')->group(function () {
                    Route::get('/', [\Modules\AuthModule\Http\Controllers\Api\ProfileApiController::class, 'show'])->name('show');
                    Route::put('/', [\Modules\AuthModule\Http\Controllers\Api\ProfileApiController::class, 'update'])->name('update');
                    Route::post('/avatar', [\Modules\AuthModule\Http\Controllers\Api\ProfileApiController::class, 'updateAvatar'])->name('avatar.update');
                    Route::delete('/avatar', [\Modules\AuthModule\Http\Controllers\Api\ProfileApiController::class, 'deleteAvatar'])->name('avatar.delete');
                    Route::post('/change-password', [\Modules\AuthModule\Http\Controllers\Api\ProfileApiController::class, 'changePassword'])->name('change-password');
                });
            });
    }

    /**
     * Load social auth routes
     */
    protected function loadSocialRoutes(): void
    {
        // Web routes
        Route::middleware(config('auth-module.routes.middleware.guest', ['web', 'guest']))
            ->prefix(config('auth-module.routes.prefix', ''))
            ->group(function () {
                Route::get(
                    'auth/{provider}',
                    [\Modules\AuthModule\Http\Controllers\Auth\Social\SocialAuthController::class, 'redirect']
                )->name('social.redirect');

                Route::get(
                    'auth/{provider}/callback',
                    [\Modules\AuthModule\Http\Controllers\Auth\Social\SocialAuthController::class, 'callback']
                )->name('social.callback');
            });

        // API routes
        Route::middleware(config('auth-module.api.middleware.guest', ['api']))
            ->prefix(config('auth-module.api.prefix', 'api/auth'))
            ->group(function () {
                Route::get(
                    'social/{provider}/redirect',
                    [\Modules\AuthModule\Http\Controllers\Api\SocialAuthApiController::class, 'redirect']
                )->name('api.social.redirect');

                Route::get(
                    'social/{provider}/callback',
                    [\Modules\AuthModule\Http\Controllers\Api\SocialAuthApiController::class, 'callback']
                )->name('api.social.callback');
            });

        Route::middleware(config('auth-module.api.middleware.auth', ['api', 'auth:sanctum']))
            ->prefix(config('auth-module.api.prefix', 'api/auth'))
            ->group(function () {
                Route::get(
                    'social/accounts',
                    [\Modules\AuthModule\Http\Controllers\Api\SocialAuthApiController::class, 'accounts']
                )->name('api.social.accounts');

                Route::delete(
                    'social/{provider}/disconnect',
                    [\Modules\AuthModule\Http\Controllers\Api\SocialAuthApiController::class, 'disconnect']
                )                ->name('api.social.disconnect');
            });
    }

    /**
     * Otomatik olarak User model'ine trait'leri ekle
     */
    protected function autoInstallTraits(): void
    {
        $userModelPath = app_path('Models/User.php');

        if (!\Illuminate\Support\Facades\File::exists($userModelPath)) {
            return;
        }

        $content = \Illuminate\Support\Facades\File::get($userModelPath);

        // Trait'ler zaten eklenmiş mi kontrol et
        $hasSocialAccounts = str_contains($content, 'HasSocialAccounts');
        $hasTenantAndRole = str_contains($content, 'HasTenantAndRole');

        if ($hasSocialAccounts && $hasTenantAndRole) {
            return; // Zaten eklenmiş
        }

        // Otomatik olarak ekle (sessiz mod)
        try {
            \Illuminate\Support\Facades\Artisan::call('auth-module:install', ['--quiet' => true]);
        } catch (\Exception $e) {
            // Sessizce devam et, kullanıcı manuel olarak çalıştırabilir
        }
    }
}
