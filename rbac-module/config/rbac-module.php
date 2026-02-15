<?php

return [
    /*
    |--------------------------------------------------------------------------
    | RBAC Configuration
    |--------------------------------------------------------------------------
    |
    | Role-Based Access Control ayarları.
    |
    */

    'enabled' => env('RBAC_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Default Roles
    |--------------------------------------------------------------------------
    |
    | Varsayılan roller ve seviyeleri.
    |
    */

    'default_roles' => [
        'admin' => [
            'level' => 100,
            'display_name' => 'Yönetici',
        ],
        'manager' => [
            'level' => 50,
            'display_name' => 'Yönetici',
        ],
        'user' => [
            'level' => 10,
            'display_name' => 'Kullanıcı',
        ],
    ],
];
