<?php

namespace Modules\RbacModule\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\RbacModule\Services\AuditLogService;
use Modules\RbacModule\Services\PermissionService;
use Modules\RbacModule\Services\TenantExportService;

class RbacModuleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/rbac-module.php',
            'rbac-module'
        );

        // Services'i singleton olarak kaydet
        $this->app->singleton(AuditLogService::class, function ($app) {
            return new AuditLogService();
        });

        $this->app->singleton(PermissionService::class, function ($app) {
            return new PermissionService();
        });

        $this->app->singleton(TenantExportService::class, function ($app) {
            return new TenantExportService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/../../config/rbac-module.php' => config_path('rbac-module.php'),
        ], 'rbac-module-config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../../database/migrations' => database_path('migrations'),
        ], 'rbac-module-migrations');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }
}
