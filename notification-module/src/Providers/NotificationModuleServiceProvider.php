<?php

namespace Modules\NotificationModule\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\NotificationModule\Services\NotificationService;

class NotificationModuleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/notification-module.php',
            'notification-module'
        );

        // Interface binding
        $this->app->singleton(
            \Modules\NotificationModule\Contracts\NotificationServiceInterface::class,
            \Modules\NotificationModule\Services\NotificationManagerService::class
        );

        // NotificationService'i singleton olarak kaydet (mail/sms için)
        $this->app->singleton(NotificationService::class, function ($app) {
            return new NotificationService();
        });

        // NotificationManagerService'i singleton olarak kaydet (bildirim yönetimi için)
        $this->app->singleton(\Modules\NotificationModule\Services\NotificationManagerService::class, function ($app) {
            return new \Modules\NotificationModule\Services\NotificationManagerService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/../../config/notification-module.php' => config_path('notification-module.php'),
        ], 'notification-module-config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../../database/migrations' => database_path('migrations'),
        ], 'notification-module-migrations');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Load API routes
        if (config('notification-module.api.enabled', true)) {
            $this->loadApiRoutes();
        }
    }

    /**
     * Load API routes
     */
    protected function loadApiRoutes(): void
    {
        \Illuminate\Support\Facades\Route::middleware(config('notification-module.api.middleware', ['api', 'auth:sanctum']))
            ->prefix(config('notification-module.api.prefix', 'api/notifications'))
            ->group(function () {
                \Illuminate\Support\Facades\Route::get('/', [\Modules\NotificationModule\Http\Controllers\Api\NotificationApiController::class, 'index'])->name('api.notifications.index');
                \Illuminate\Support\Facades\Route::post('/', [\Modules\NotificationModule\Http\Controllers\Api\NotificationApiController::class, 'store'])->name('api.notifications.store');
                \Illuminate\Support\Facades\Route::get('/unread-count', [\Modules\NotificationModule\Http\Controllers\Api\NotificationApiController::class, 'unreadCount'])->name('api.notifications.unread-count');
                \Illuminate\Support\Facades\Route::post('/{id}/mark-as-read', [\Modules\NotificationModule\Http\Controllers\Api\NotificationApiController::class, 'markAsRead'])->name('api.notifications.mark-as-read');
                \Illuminate\Support\Facades\Route::post('/mark-all-as-read', [\Modules\NotificationModule\Http\Controllers\Api\NotificationApiController::class, 'markAllAsRead'])->name('api.notifications.mark-all-as-read');
                \Illuminate\Support\Facades\Route::delete('/{id}', [\Modules\NotificationModule\Http\Controllers\Api\NotificationApiController::class, 'destroy'])->name('api.notifications.destroy');
            });
    }
}
