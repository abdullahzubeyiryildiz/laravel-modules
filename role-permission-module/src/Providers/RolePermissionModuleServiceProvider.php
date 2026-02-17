<?php

namespace Modules\RolePermissionModule\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\RolePermissionModule\Contracts\RolePermissionServiceInterface;
use Modules\RolePermissionModule\Services\RolePermissionService;

class RolePermissionModuleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/role-permission-module.php',
            'role-permission-module'
        );

        $this->app->singleton(RolePermissionServiceInterface::class, RolePermissionService::class);
        $this->app->singleton(RolePermissionService::class, RolePermissionService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if (!config('role-permission-module.enabled', true)) {
            return;
        }

        $this->publishes([
            __DIR__ . '/../../config/role-permission-module.php' => config_path('role-permission-module.php'),
        ], 'role-permission-module-config');

        $this->publishes([
            __DIR__ . '/../../database/migrations' => database_path('migrations'),
        ], 'role-permission-module-migrations');

        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Modules\RolePermissionModule\Commands\SeedRolePermissionCommand::class,
            ]);
        }
    }
}
