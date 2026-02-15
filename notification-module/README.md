# Notification Module

Reusable notification module for Laravel with Mail and SMS support. Supports multiple providers.

## Özellikler

- ✅ Mail gönderme (SMTP, Mailgun, SendGrid vb.)
- ✅ SMS gönderme (Mutlucell, NetGSM vb.)
- ✅ Provider pattern - kolayca yeni sağlayıcılar eklenebilir
- ✅ Template sistemi
- ✅ Mail ve SMS birlikte veya ayrı ayrı gönderme
- ✅ Reusable - başka modüllerde kullanılabilir

## Kurulum

### 1. Paketi Yükle

```bash
composer require modules/notification-module
```

### 2. Config Yayınla

```bash
php artisan vendor:publish --tag=notification-module-config
```

### 3. Environment Variables

`.env` dosyasına ekleyin:

```env
# Mail Ayarları
NOTIFICATION_MAIL_ENABLED=true
NOTIFICATION_MAIL_PROVIDER=smtp

# SMS Ayarları
NOTIFICATION_SMS_ENABLED=true
NOTIFICATION_SMS_PROVIDER=mutlucell

# Mutlucell SMS
MUTLUCELL_USERNAME=your_username
MUTLUCELL_PASSWORD=your_password
MUTLUCELL_ORIGINATOR=MODULPANEL

# Mailgun (opsiyonel)
MAILGUN_DOMAIN=your_domain
MAILGUN_SECRET=your_api_key
```

## Kullanım

### Basit Kullanım

```php
use Modules\NotificationModule\Services\NotificationService;

$notificationService = app(NotificationService::class);

// Mail gönder
$notificationService->sendMail(
    'user@example.com',
    'Hoş Geldiniz',
    'Merhaba, hesabınız oluşturuldu!'
);

// SMS gönder
$notificationService->sendSms(
    '05551234567',
    'Hoş geldiniz! Hesabınız oluşturuldu.'
);

// Mail ve SMS birlikte
$notificationService->sendBoth(
    'user@example.com',
    '05551234567',
    'Hoş Geldiniz',
    'Mail içeriği...',
    'SMS mesajı...'
);
```

### Template Kullanımı

```php
$templates = config('notification-module.templates.welcome');

$notificationService->sendMail(
    $user->email,
    str_replace(':name', $user->name, $templates['mail']['subject']),
    str_replace(':name', $user->name, $templates['mail']['body'])
);
```

## Mail Sağlayıcıları

### SMTP (Varsayılan)

Laravel'in standart mail yapılandırmasını kullanır.

```env
NOTIFICATION_MAIL_PROVIDER=smtp
```

### Mailgun

```env
NOTIFICATION_MAIL_PROVIDER=mailgun
MAILGUN_DOMAIN=your_domain
MAILGUN_SECRET=your_api_key
```

### Yeni Mail Sağlayıcı Ekleme

1. `MailProviderInterface` implement eden bir class oluştur:

```php
namespace Modules\NotificationModule\Services\Mail;

use Modules\NotificationModule\Contracts\MailProviderInterface;

class SendGridMailService implements MailProviderInterface
{
    public function send(string $to, string $subject, string $body, array $options = []): bool
    {
        // SendGrid API entegrasyonu
    }

    public function sendBulk(array $recipients, string $subject, string $body, array $options = []): bool
    {
        // Bulk mail gönderme
    }
}
```

2. Config'e ekle:

```php
'providers' => [
    'sendgrid' => [
        'class' => \Modules\NotificationModule\Services\Mail\SendGridMailService::class,
        'api_key' => env('SENDGRID_API_KEY'),
    ],
],
```

## SMS Sağlayıcıları

### Mutlucell (Varsayılan)

```env
NOTIFICATION_SMS_PROVIDER=mutlucell
MUTLUCELL_USERNAME=your_username
MUTLUCELL_PASSWORD=your_password
MUTLUCELL_ORIGINATOR=MODULPANEL
```

### Yeni SMS Sağlayıcı Ekleme

1. `SmsProviderInterface` implement eden bir class oluştur:

```php
namespace Modules\NotificationModule\Services\Sms;

use Modules\NotificationModule\Contracts\SmsProviderInterface;

class NetGsmSmsService implements SmsProviderInterface
{
    public function send(string $phone, string $message, array $options = []): bool
    {
        // NetGSM API entegrasyonu
    }

    public function sendBulk(array $phones, string $message, array $options = []): bool
    {
        // Bulk SMS gönderme
    }
}
```

2. Config'e ekle:

```php
'providers' => [
    'netgsm' => [
        'class' => \Modules\NotificationModule\Services\Sms\NetGsmSmsService::class,
        'username' => env('NETGSM_USERNAME'),
        'password' => env('NETGSM_PASSWORD'),
    ],
],
```

## Auth Modülü Entegrasyonu

Notification modülü auth modülüne otomatik entegre edilir. Kullanıcı kayıt olduğunda:

- Mail gönderilir (eğer `NOTIFICATION_MAIL_ENABLED=true`)
- SMS gönderilir (eğer `NOTIFICATION_SMS_ENABLED=true` ve kullanıcının telefonu varsa)

## Başka Modülde Kullanım

```php
use Modules\NotificationModule\Services\NotificationService;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $order = Order::create($request->all());

        // Sipariş onay maili gönder
        $notificationService = app(NotificationService::class);
        $notificationService->sendMail(
            $order->user->email,
            'Siparişiniz Alındı',
            "Sipariş #{$order->id} başarıyla oluşturuldu."
        );

        return response()->json($order);
    }
}
```

## Lisans

MIT
