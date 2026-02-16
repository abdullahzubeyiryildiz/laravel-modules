<?php

namespace Modules\UserManagementModule\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class UserManagementModuleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Config publish
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/user-management-module.php',
            'user-management-module'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/../../config/user-management-module.php' => config_path('user-management-module.php'),
        ], 'user-management-module-config');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'user-management-module');

        // Publish views
        $this->publishes([
            __DIR__ . '/../../resources/views' => resource_path('views/vendor/user-management-module'),
        ], 'user-management-module-views');

        // Load routes
        $this->loadRoutes();
    }

    /**
     * Load module routes
     */
    protected function loadRoutes(): void
    {
        Route::middleware('auth')->prefix('admin/users')->name('admin.users.')->group(function () {
            Route::get('/', [\Modules\UserManagementModule\Http\Controllers\Admin\UserController::class, 'index'])->name('index');
            Route::post('/datatable', [\Modules\UserManagementModule\Http\Controllers\Admin\UserController::class, 'datatable'])->name('datatable');
            Route::post('/', [\Modules\UserManagementModule\Http\Controllers\Admin\UserController::class, 'store'])->name('store');
            Route::get('/{user}', [\Modules\UserManagementModule\Http\Controllers\Admin\UserController::class, 'show'])->name('show');
            Route::put('/{user}', [\Modules\UserManagementModule\Http\Controllers\Admin\UserController::class, 'update'])->name('update');
            Route::delete('/{user}', [\Modules\UserManagementModule\Http\Controllers\Admin\UserController::class, 'destroy'])->name('destroy');
            Route::post('/{user}/toggle-status', [\Modules\UserManagementModule\Http\Controllers\Admin\UserController::class, 'toggleStatus'])->name('toggle-status');
        });
    }
}
