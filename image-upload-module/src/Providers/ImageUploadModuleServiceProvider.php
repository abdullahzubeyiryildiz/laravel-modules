<?php

namespace Modules\ImageUploadModule\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\ImageUploadModule\Services\ImageUploadService;

class ImageUploadModuleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/image-upload-module.php',
            'image-upload-module'
        );

        // Interface binding
        $this->app->singleton(
            \Modules\ImageUploadModule\Contracts\ImageUploadServiceInterface::class,
            ImageUploadService::class
        );

        // Service'i singleton olarak kaydet
        $this->app->singleton(ImageUploadService::class, function ($app) {
            return new ImageUploadService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/../../config/image-upload-module.php' => config_path('image-upload-module.php'),
        ], 'image-upload-module-config');
    }
}
