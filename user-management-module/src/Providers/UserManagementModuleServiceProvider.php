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

        // Load routes early in register() to ensure they're available
        // This is important for route discovery and registration
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
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

        // Publish routes
        $this->publishes([
            __DIR__ . '/../../routes/web.php' => base_path('routes/user-management-module.php'),
        ], 'user-management-module-routes');

        // Merge JSON translations to main lang directory
        $this->mergeJsonTranslations();
    }

    /**
     * Modül JSON çevirilerini ana lang dizinine merge et
     */
    protected function mergeJsonTranslations(): void
    {
        $moduleLangPath = __DIR__ . '/../../lang';
        $locales = ['tr', 'en'];

        foreach ($locales as $locale) {
            $moduleFile = $moduleLangPath . '/' . $locale . '.json';
            $mainFile = lang_path($locale . '.json');

            if (!file_exists($moduleFile)) {
                continue;
            }

            // Modül çevirilerini oku
            $moduleTranslations = json_decode(file_get_contents($moduleFile), true);
            if (!is_array($moduleTranslations)) {
                continue;
            }

            // Ana dosya varsa oku, yoksa boş array
            $mainTranslations = [];
            if (file_exists($mainFile)) {
                $mainTranslations = json_decode(file_get_contents($mainFile), true);
                if (!is_array($mainTranslations)) {
                    $mainTranslations = [];
                }
            }

            // Modül çevirilerini ana çevirilere merge et (modül çevirileri öncelikli)
            $mergedTranslations = array_merge($mainTranslations, $moduleTranslations);

            // Ana dosyayı güncelle
            file_put_contents($mainFile, json_encode($mergedTranslations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }

}
