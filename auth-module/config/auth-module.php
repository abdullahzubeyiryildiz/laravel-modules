<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Routes Configuration
    |--------------------------------------------------------------------------
    |
    | Bu ayarlar authentication route'larını yapılandırır.
    |
    */

    'routes' => [
        'enabled' => env('AUTH_MODULE_ROUTES_ENABLED', true),
        'prefix' => env('AUTH_MODULE_ROUTES_PREFIX', ''),
        'middleware' => [
            'guest' => ['web', 'guest'],
            'auth' => ['web', 'auth'],
        ],
        'login' => env('AUTH_MODULE_ROUTE_LOGIN', 'login'),
        'register' => env('AUTH_MODULE_ROUTE_REGISTER', 'register'),
        'logout' => env('AUTH_MODULE_ROUTE_LOGOUT', 'logout'),
        'password' => [
            'request' => env('AUTH_MODULE_ROUTE_PASSWORD_REQUEST', 'password/reset'),
            'email' => env('AUTH_MODULE_ROUTE_PASSWORD_EMAIL', 'password/email'),
            'reset' => env('AUTH_MODULE_ROUTE_PASSWORD_RESET', 'password/reset/{token}'),
            'update' => env('AUTH_MODULE_ROUTE_PASSWORD_UPDATE', 'password/reset'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | View Configuration
    |--------------------------------------------------------------------------
    |
    | Authentication view'larının yapılandırması.
    |
    */

    'views' => [
        'login' => env('AUTH_MODULE_VIEW_LOGIN', 'pages.auth.signin'),
        'register' => env('AUTH_MODULE_VIEW_REGISTER', 'pages.auth.signup'),
        'password' => [
            'request' => env('AUTH_MODULE_VIEW_PASSWORD_REQUEST', 'auth-module::auth.passwords.email'),
            'reset' => env('AUTH_MODULE_VIEW_PASSWORD_RESET', 'auth-module::auth.passwords.reset'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Redirect Configuration
    |--------------------------------------------------------------------------
    |
    | Başarılı giriş/kayıt sonrası yönlendirme ayarları.
    |
    */

    'redirects' => [
        'after_login' => env('AUTH_MODULE_REDIRECT_AFTER_LOGIN', '/dashboard'),
        'after_register' => env('AUTH_MODULE_REDIRECT_AFTER_REGISTER', '/dashboard'),
        'after_logout' => env('AUTH_MODULE_REDIRECT_AFTER_LOGOUT', '/login'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-Tenant Configuration
    |--------------------------------------------------------------------------
    |
    | Multi-tenant desteği için ayarlar.
    |
    */

    'multi_tenant' => [
        'enabled' => env('AUTH_MODULE_MULTI_TENANT', false), // Varsayılan: false (multi-tenant kapalı)
        'tenant_helper_class' => env('AUTH_MODULE_TENANT_HELPER', 'App\Helpers\TenantHelper'),
        'user_model' => env('AUTH_MODULE_USER_MODEL', 'App\Models\User'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    |
    | Form validasyon kuralları.
    |
    */

    'validation' => [
        'password_min_length' => env('AUTH_MODULE_PASSWORD_MIN', 6),
        'password_require_uppercase' => env('AUTH_MODULE_PASSWORD_UPPERCASE', false),
        'password_require_lowercase' => env('AUTH_MODULE_PASSWORD_LOWERCASE', false),
        'password_require_numbers' => env('AUTH_MODULE_PASSWORD_NUMBERS', false),
        'password_require_symbols' => env('AUTH_MODULE_PASSWORD_SYMBOLS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Configuration
    |--------------------------------------------------------------------------
    |
    | Şifre sıfırlama email ayarları.
    |
    */

    'email' => [
        'from' => [
            'address' => env('AUTH_MODULE_EMAIL_FROM_ADDRESS', env('MAIL_FROM_ADDRESS')),
            'name' => env('AUTH_MODULE_EMAIL_FROM_NAME', env('MAIL_FROM_NAME')),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    |
    | API endpoint'leri için ayarlar.
    |
    */

    'api' => [
        'enabled' => env('AUTH_MODULE_API_ENABLED', true),
        'prefix' => env('AUTH_MODULE_API_PREFIX', 'api/auth'),
        'middleware' => [
            'guest' => ['api'],
            'auth' => ['api', 'auth:sanctum'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Permissions Configuration
    |--------------------------------------------------------------------------
    |
    | Rol bazlı yetki tanımları.
    |
    */

    'permissions' => [
        'admin' => ['*'], // Admin tüm yetkilere sahip
        'manager' => [
            'users.view',
            'users.edit',
            'users.delete',
            'content.view',
            'content.edit',
        ],
        'moderator' => [
            'content.view',
            'content.edit',
            'comments.moderate',
        ],
        'editor' => [
            'content.view',
            'content.edit',
        ],
        'user' => [
            'profile.view',
            'profile.edit',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Social Authentication
    |--------------------------------------------------------------------------
    |
    | Sosyal medya giriş ayarları (Google, Facebook, GitHub, vb.)
    |
    */

    'social' => [
        'enabled' => env('AUTH_MODULE_SOCIAL_ENABLED', false),
        'providers' => [
            'google' => [
                'enabled' => env('AUTH_MODULE_SOCIAL_GOOGLE_ENABLED', false),
                'client_id' => env('GOOGLE_CLIENT_ID'),
                'client_secret' => env('GOOGLE_CLIENT_SECRET'),
                'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
            ],
            'facebook' => [
                'enabled' => env('AUTH_MODULE_SOCIAL_FACEBOOK_ENABLED', false),
                'client_id' => env('FACEBOOK_CLIENT_ID'),
                'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
                'redirect' => env('FACEBOOK_REDIRECT_URI', '/auth/facebook/callback'),
            ],
            'github' => [
                'enabled' => env('AUTH_MODULE_SOCIAL_GITHUB_ENABLED', false),
                'client_id' => env('GITHUB_CLIENT_ID'),
                'client_secret' => env('GITHUB_CLIENT_SECRET'),
                'redirect' => env('GITHUB_REDIRECT_URI', '/auth/github/callback'),
            ],
            'twitter' => [
                'enabled' => env('AUTH_MODULE_SOCIAL_TWITTER_ENABLED', false),
                'client_id' => env('TWITTER_CLIENT_ID'),
                'client_secret' => env('TWITTER_CLIENT_SECRET'),
                'redirect' => env('TWITTER_REDIRECT_URI', '/auth/twitter/callback'),
            ],
        ],
    ],
];
