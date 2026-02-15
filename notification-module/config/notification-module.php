<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mail Configuration
    |--------------------------------------------------------------------------
    |
    | Mail gönderme ayarları.
    |
    */

    'mail' => [
        'enabled' => env('NOTIFICATION_MAIL_ENABLED', true),
        'provider' => env('NOTIFICATION_MAIL_PROVIDER', 'smtp'), // smtp, mailgun, sendgrid

        'providers' => [
            'smtp' => [
                'class' => \Modules\NotificationModule\Services\Mail\SmtpMailService::class,
            ],
            'mailgun' => [
                'class' => \Modules\NotificationModule\Services\Mail\MailgunMailService::class,
                'domain' => env('MAILGUN_DOMAIN'),
                'api_key' => env('MAILGUN_SECRET'),
            ],
            // SendGrid ve diğer sağlayıcılar buraya eklenebilir
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Configuration
    |--------------------------------------------------------------------------
    |
    | SMS gönderme ayarları.
    |
    */

    'sms' => [
        'enabled' => env('NOTIFICATION_SMS_ENABLED', true),
        'provider' => env('NOTIFICATION_SMS_PROVIDER', 'mutlucell'), // mutlucell, netgsm, vb.

        'providers' => [
            'mutlucell' => [
                'class' => \Modules\NotificationModule\Services\Sms\MutlucellSmsService::class,
                'username' => env('MUTLUCELL_USERNAME'),
                'password' => env('MUTLUCELL_PASSWORD'),
                'originator' => env('MUTLUCELL_ORIGINATOR', 'MODULPANEL'),
                'api_url' => env('MUTLUCELL_API_URL', 'https://api.mutlucell.com/send'),
            ],
            // NetGSM ve diğer SMS sağlayıcılar buraya eklenebilir
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Templates
    |--------------------------------------------------------------------------
    |
    | Mail ve SMS şablonları.
    |
    */

    'templates' => [
        'welcome' => [
            'mail' => [
                'subject' => 'Hoş Geldiniz!',
                'body' => 'Merhaba :name, hesabınız başarıyla oluşturuldu.',
            ],
            'sms' => 'Merhaba :name, hesabınız başarıyla oluşturuldu. Hoş geldiniz!',
        ],
        'password_reset' => [
            'mail' => [
                'subject' => 'Şifre Sıfırlama',
                'body' => 'Şifre sıfırlama linkiniz: :link',
            ],
            'sms' => 'Şifre sıfırlama kodunuz: :code',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    |
    | Bildirim API ayarları
    |
    */

    'api' => [
        'enabled' => env('NOTIFICATION_MODULE_API_ENABLED', true),
        'prefix' => env('NOTIFICATION_MODULE_API_PREFIX', 'api/notifications'),
        'middleware' => env('NOTIFICATION_MODULE_API_MIDDLEWARE', ['api', 'auth:sanctum']),
    ],
];
