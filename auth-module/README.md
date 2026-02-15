# Modul Auth Module

Laravel authentication modülü. Login, Register ve Password Reset özelliklerini içerir. Hem tek tenant hem de multi-tenant uygulamalarda kullanılabilir. Web ve API endpoint'leri ile Next.js entegrasyonu destekler.

## Özellikler

- ✅ Kullanıcı Girişi (Login)
- ✅ Kullanıcı Kaydı (Register)
- ✅ Şifre Sıfırlama (Password Reset)
- ✅ Multi-Tenant Desteği
- ✅ Özelleştirilebilir Route'lar
- ✅ Özelleştirilebilir View'lar
- ✅ Config dosyası ile tam kontrol

## Kurulum

### 1. Paketi Yükle

```bash
composer require modules/auth-module
```

### 2. Config Yayınla

```bash
php artisan vendor:publish --tag=auth-module-config
```

### 3. (Opsiyonel) API için Sanctum Kur

API endpoint'lerini kullanmak için:

```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

User modelinize ekleyin:
```php
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
}
```

### 4. Hazır! 🎉

**Multi-tenant olmadan kullanım için başka bir şey yapmanıza gerek yok!**

Paket varsayılan olarak multi-tenant kapalı çalışır. Sadece standart Laravel User modeliniz olması yeterli.

### Manuel Kurulum

1. Paketi `packages/modules/auth-module` klasörüne kopyalayın
2. `composer.json` dosyanıza ekleyin:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./packages/modules/auth-module"
        }
    ],
    "require": {
        "modules/auth-module": "*"
    }
}
```

3. Composer'ı güncelleyin:

```bash
composer update
```

## Yapılandırma

### Config Dosyasını Yayınla

```bash
php artisan vendor:publish --tag=auth-module-config
```

Bu komut `config/auth-module.php` dosyasını oluşturur.

### Migration'ları Yayınla

```bash
php artisan vendor:publish --tag=auth-module-migrations
php artisan migrate
```

### View'ları Yayınla (İsteğe Bağlı)

View'ları özelleştirmek için:

```bash
php artisan vendor:publish --tag=auth-module-views
```

View'lar `resources/views/vendor/auth-module` klasörüne kopyalanır.

## Kullanım

### Route'lar

Paket otomatik olarak aşağıdaki route'ları oluşturur:

- `GET /login` - Giriş sayfası
- `POST /login` - Giriş işlemi
- `GET /register` - Kayıt sayfası
- `POST /register` - Kayıt işlemi
- `POST /logout` - Çıkış işlemi
- `GET /password/reset` - Şifre sıfırlama isteği
- `POST /password/email` - Şifre sıfırlama linki gönder
- `GET /password/reset/{token}` - Şifre sıfırlama formu
- `POST /password/reset` - Şifre sıfırlama işlemi

### Multi-Tenant Kullanımı (Opsiyonel)

**Varsayılan olarak multi-tenant kapalıdır.** Multi-tenant kullanmak istiyorsanız:

### 1. Config'i Güncelle

`.env` dosyasına ekleyin:
```env
AUTH_MODULE_MULTI_TENANT=true
AUTH_MODULE_TENANT_HELPER=App\Helpers\TenantHelper
```

### 2. TenantHelper Sınıfı

`TenantHelper` sınıfınızda şu metodlar olmalı:

```php
class TenantHelper
{
    public static function current()
    {
        // Mevcut tenant'ı döndür
    }
    
    public static function id()
    {
        // Mevcut tenant ID'sini döndür
    }
}
```

### Multi-Tenant Olmadan Kullanım

Multi-tenant olmadan kullanmak için hiçbir şey yapmanıza gerek yok! Paket varsayılan olarak multi-tenant kapalı çalışır.

Sadece standart User modeliniz olması yeterli:
```php
class User extends Authenticatable
{
    use HasFactory, Notifiable;
    // tenant_id field'ı olmasa bile çalışır
}
```

### Route Özelleştirme

Route'ları özelleştirmek için `.env` dosyanıza ekleyin:

```env
AUTH_MODULE_ROUTES_PREFIX=admin
AUTH_MODULE_ROUTE_LOGIN=admin/login
AUTH_MODULE_ROUTE_REGISTER=admin/register
```

### Redirect Özelleştirme

Başarılı giriş/kayıt sonrası yönlendirmeleri özelleştirmek için:

```env
AUTH_MODULE_REDIRECT_AFTER_LOGIN=/dashboard
AUTH_MODULE_REDIRECT_AFTER_REGISTER=/dashboard
AUTH_MODULE_REDIRECT_AFTER_LOGOUT=/login
```

## View Özelleştirme

View'ları özelleştirmek için:

1. View'ları yayınlayın:
```bash
php artisan vendor:publish --tag=auth-module-views
```

2. `resources/views/vendor/auth-module` klasöründeki view'ları düzenleyin.

## API Kullanımı

Paket RESTful API endpoint'leri sağlar. Detaylı API dokümantasyonu için `API_DOKUMANTASYON.md` dosyasına bakın.

### API Endpoint'leri

- `POST /api/auth/login` - Kullanıcı girişi
- `POST /api/auth/register` - Kullanıcı kaydı
- `POST /api/auth/logout` - Kullanıcı çıkışı (Auth required)
- `GET /api/auth/me` - Mevcut kullanıcı bilgileri (Auth required)
- `POST /api/auth/password/request` - Şifre sıfırlama isteği
- `POST /api/auth/password/reset` - Şifre sıfırlama

### Sanctum Kurulumu

API kullanımı için Laravel Sanctum gereklidir:

```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

### User Model Güncellemesi

User modelinize `HasApiTokens` trait'ini ekleyin:

```php
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    // ...
}
```

### CORS Yapılandırması

Next.js veya diğer frontend'lerden API'ye erişim için CORS ayarlarını yapın:

```php
// config/cors.php
'allowed_origins' => [
    'http://localhost:3000',
    'https://your-nextjs-domain.com',
],
```

## Gereksinimler

- PHP >= 8.2
- Laravel >= 12.0
- Laravel Sanctum >= 4.0 (Sadece API kullanımı için - opsiyonel)

## Multi-Tenant Olmadan Kullanım

Paket varsayılan olarak **multi-tenant kapalı** çalışır. Hiçbir ek yapılandırma gerekmez!

Detaylı bilgi için `MULTI_TENANT_OLMADAN.md` dosyasına bakın.

## Lisans

MIT

## Katkıda Bulunma

Pull request'ler memnuniyetle karşılanır. Büyük değişiklikler için lütfen önce bir issue açarak neyi değiştirmek istediğinizi tartışın.

## Destek

Sorularınız için issue açabilirsiniz.
