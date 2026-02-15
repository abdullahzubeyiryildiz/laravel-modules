<?php

namespace Modules\FileManagerModule\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Modules\FileManagerModule\Models\File;
use Modules\FileManagerModule\Scopes\TenantScope;
use Modules\FileManagerModule\Services\FileManagerService;

class FileManagerModuleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/file-manager-module.php',
            'file-manager-module'
        );

        // Interface binding
        $this->app->singleton(
            \Modules\FileManagerModule\Contracts\FileManagerServiceInterface::class,
            FileManagerService::class
        );

        // FileManagerService'i singleton olarak kaydet
        $this->app->singleton(FileManagerService::class, function ($app) {
            $imageUploadService = null;
            if (class_exists(\Modules\ImageUploadModule\Contracts\ImageUploadServiceInterface::class)) {
                $imageUploadService = $this->app->make(\Modules\ImageUploadModule\Contracts\ImageUploadServiceInterface::class);
            }
            return new FileManagerService($imageUploadService);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/../../config/file-manager-module.php' => config_path('file-manager-module.php'),
        ], 'file-manager-module-config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../../database/migrations' => database_path('migrations'),
        ], 'file-manager-module-migrations');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Global scope ekle (tenant izolasyonu için)
        if (config('auth-module.multi_tenant.enabled', false)) {
            File::addGlobalScope(new TenantScope());
        }

        // Load API routes
        if (config('file-manager-module.api.enabled', true)) {
            $this->loadApiRoutes();
        }

        // Load web routes (download)
        $this->loadWebRoutes();
    }

    /**
     * Load web routes
     */
    protected function loadWebRoutes(): void
    {
        Route::get('/file/download/{token}', [\Modules\FileManagerModule\Http\Controllers\FileDownloadController::class, 'download'])
            ->name('file.download');
    }

    /**
     * Load API routes
     */
    protected function loadApiRoutes(): void
    {
        Route::middleware(config('file-manager-module.api.middleware', ['api', 'auth:sanctum']))
            ->prefix(config('file-manager-module.api.prefix', 'api/files'))
            ->group(function () {
                Route::post('/', [\Modules\FileManagerModule\Http\Controllers\Api\FileApiController::class, 'upload'])->name('api.files.upload');
                Route::get('/', [\Modules\FileManagerModule\Http\Controllers\Api\FileApiController::class, 'index'])->name('api.files.index');
                Route::get('/{id}', [\Modules\FileManagerModule\Http\Controllers\Api\FileApiController::class, 'show'])->name('api.files.show');
                Route::delete('/{id}', [\Modules\FileManagerModule\Http\Controllers\Api\FileApiController::class, 'destroy'])->name('api.files.destroy');
                Route::post('/{id}/signed-url', [\Modules\FileManagerModule\Http\Controllers\Api\FileApiController::class, 'getSignedUrl'])->name('api.files.signed-url');

                // Alt text güncelleme
                Route::put('/{id}/alt-text', [\Modules\FileManagerModule\Http\Controllers\Api\FileSeoApiController::class, 'update'])->name('api.files.alt-text.update');
            });
    }
}
