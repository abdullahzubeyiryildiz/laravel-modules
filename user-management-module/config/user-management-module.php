<?php

return [
    /*
    |--------------------------------------------------------------------------
    | User Management Module Configuration
    |--------------------------------------------------------------------------
    |
    | Bu dosya user-management-module için yapılandırma ayarlarını içerir.
    |
    */

    'user_model' => \App\Models\User::class,

    'per_page' => 10,

    'roles' => [
        'admin' => 'Admin',
        'manager' => 'Yönetici',
        'user' => 'Kullanıcı',
    ],
];
