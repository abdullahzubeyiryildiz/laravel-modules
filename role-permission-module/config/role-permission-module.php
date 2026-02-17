<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Role Permission Module Configuration
    |--------------------------------------------------------------------------
    |
    | Dinamik rol ve izin yönetimi. auth-module ve rbac-module tarafından
    | opsiyonel olarak kullanılabilir.
    |
    */

    'enabled' => env('ROLE_PERMISSION_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    */

    'user_model' => env('ROLE_PERMISSION_USER_MODEL', 'App\Models\User'),

    /*
    |--------------------------------------------------------------------------
    | Multi-Tenant Support
    |--------------------------------------------------------------------------
    |
    | Tenant bazlı rol/permission desteği.
    |
    */

    'multi_tenant' => [
        'enabled' => env('ROLE_PERMISSION_MULTI_TENANT', false),
        'tenant_helper_class' => env('ROLE_PERMISSION_TENANT_HELPER', 'App\Helpers\TenantHelper'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Role
    |--------------------------------------------------------------------------
    |
    | Yeni kullanıcı kaydında atanacak varsayılan rol slug'ı.
    |
    */

    'default_role_slug' => env('ROLE_PERMISSION_DEFAULT_ROLE', 'user'),

    /*
    |--------------------------------------------------------------------------
    | Super Admin Role
    |--------------------------------------------------------------------------
    |
    | Tüm izinlere sahip olan rol slug'ı (bypass).
    |
    */

    'super_admin_slug' => env('ROLE_PERMISSION_SUPER_ADMIN', 'admin'),

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    */

    'cache' => [
        'enabled' => env('ROLE_PERMISSION_CACHE', true),
        'ttl' => env('ROLE_PERMISSION_CACHE_TTL', 3600), // 1 saat
    ],
];
