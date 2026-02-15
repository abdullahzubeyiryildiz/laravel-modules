# Notification Module

Reusable notification module for Laravel with Mail and SMS support. Supports multiple providers (SMTP, Mailgun, SendGrid for mail; Mutlucell, etc. for SMS). Laravel'in built-in notification sistemi ile entegre.

## ✨ Özellikler

- ✅ Mail gönderimi (SMTP, Mailgun, SendGrid)
- ✅ SMS gönderimi (Mutlucell, vb.)
- ✅ Database notifications (Laravel'in built-in sistemi)
- ✅ Çoklu provider desteği
- ✅ Extensible yapı
- ✅ Bildirim listeleme, okundu işaretleme
- ✅ Bildirim gönderme API'si

## 📦 Kurulum

### 1. Composer.json'a Repository Ekle

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/abdullahzubeyiryildiz/laravel-modules"
        }
    ]
}
```

### 2. Paketi Yükle

```bash
composer require modules/notification-module:dev-main
```

### 3. Migration'ları Çalıştır

```bash
php artisan migrate
```

## 🚀 Kullanım

### Mail Gönderme

```php
use Modules\NotificationModule\Services\NotificationService;

$notificationService = app(NotificationService::class);

$notificationService->sendMail(
    to: 'user@example.com',
    subject: 'Hoş Geldiniz',
    message: 'Kayıt olduğunuz için teşekkürler!',
    provider: 'smtp' // veya 'mailgun'
);
```

### SMS Gönderme

```php
$notificationService->sendSms(
    phone: '+905551234567',
    message: 'Doğrulama kodunuz: 123456',
    provider: 'mutlucell'
);
```

### Database Notification

```php
use Modules\NotificationModule\Contracts\NotificationServiceInterface;

$notificationService = app(NotificationServiceInterface::class);

$notificationService->send(
    notifiable: $user,
    type: 'success',
    title: 'Sipariş Onaylandı',
    message: 'Siparişiniz başarıyla onaylandı.',
    actionUrl: '/orders/123',
    actionText: 'Siparişi Gör'
);
```

## 📋 API Endpoints

- `GET /api/notifications` - Bildirimleri listele
- `POST /api/notifications` - Bildirim gönder
- `GET /api/notifications/unread-count` - Okunmamış sayısı
- `POST /api/notifications/{id}/mark-as-read` - Okundu işaretle
- `POST /api/notifications/mark-all-as-read` - Tümünü okundu işaretle
- `DELETE /api/notifications/{id}` - Bildirim sil

## 📝 Gereksinimler

- PHP >= 8.2
- Laravel >= 12.0
- `guzzlehttp/guzzle` (SMS sağlayıcıları için - opsiyonel)

## 📝 Lisans

MIT

## 👤 Yazar

**Abdullah Zubeyir Yıldız**  
GitHub: [@abdullahzubeyiryildiz](https://github.com/abdullahzubeyiryildiz)
