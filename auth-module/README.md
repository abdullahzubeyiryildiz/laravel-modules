# Auth Module

Laravel authentication modülü. Login, Register ve Password Reset özelliklerini içerir. Hem tek tenant hem de multi-tenant uygulamalarda kullanılabilir. Web ve API endpoint'leri ile Next.js entegrasyonu destekler.

## ✨ Özellikler

- ✅ Kullanıcı Girişi (Login)
- ✅ Kullanıcı Kaydı (Register)
- ✅ Şifre Sıfırlama (Password Reset)
- ✅ Social Login (Google, Facebook) - Opsiyonel
- ✅ Multi-Tenant Desteği - Opsiyonel
- ✅ Profil Yönetimi (Edit, Avatar, Change Password)
- ✅ Otomatik Trait Kurulumu (HasSocialAccounts, HasTenantAndRole)
- ✅ Özelleştirilebilir Route'lar
- ✅ Özelleştirilebilir View'lar
- ✅ Config dosyası ile tam kontrol

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
composer require modules/auth-module:dev-main
```

### 3. Migration'ları Çalıştır

```bash
php artisan migrate
```

**✅ Trait'ler otomatik olarak User model'ine eklenir!**

Otomatik olarak eklenenler:
- `HasSocialAccounts` trait (social accounts için)
- `HasTenantAndRole` trait (tenant ve role method'ları için)

### 4. (Opsiyonel) API için Sanctum Kur

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

## 🚀 Kullanım

### Rol & Yetki Sistemi

Auth Module, kullanıcı rolleri ve izinleri için **isteğe bağlı** olarak `role-permission-module` paketini kullanır:

- Roller globaldir ve **kullanıcıya (`user_id`) göre** atanır (`user_roles` pivot tablosu).
- Bir kullanıcının birden fazla rolü olabilir (örn. `admin`, `manager`, `user`).
- Multi-tenant açıksa bile rol atama kullanıcı bazlıdır; tenant'a göre ayrı rol kopyaları oluşturulmaz.
- Varsayılan roller: `admin`, `manager`, `user` (RolePermissionSeeder ile seed edilir).

Yönetim panelinde kullanıcı oluştururken/düzenlerken seçtiğiniz rol, bu sistem üzerinden `user_roles` tablosuna yazılır ve Auth Module içindeki tüm `isAdmin`, `getUserRole` vb. kontroller bu rolleri kullanır.

### Web Routes

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

### API Endpoints

- `POST /api/auth/login` - Kullanıcı girişi
- `POST /api/auth/register` - Kullanıcı kaydı
- `POST /api/auth/logout` - Kullanıcı çıkışı (Auth required)
- `GET /api/auth/me` - Mevcut kullanıcı bilgileri (Auth required)
- `POST /api/auth/password/request` - Şifre sıfırlama isteği
- `POST /api/auth/password/reset` - Şifre sıfırlama

### Profile API

- `GET /api/auth/profile` - Profil bilgileri
- `PUT /api/auth/profile` - Profil güncelle
- `POST /api/auth/profile/avatar` - Avatar yükle
- `DELETE /api/auth/profile/avatar` - Avatar sil
- `POST /api/auth/profile/change-password` - Şifre değiştir

### Social Login (Opsiyonel)

- `GET /auth/{provider}` - Social provider'a yönlendir
- `GET /auth/{provider}/callback` - Social provider'dan dönüş

## 🔧 Yapılandırma

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

## 📋 Otomatik Kurulum

Paket kurulduğunda **otomatik olarak** User model'ine trait'ler eklenir:

- ✅ `HasSocialAccounts` - Social accounts için
- ✅ `HasTenantAndRole` - Tenant ve role method'ları için

Manuel kurulum gerekmez! Eğer otomatik eklenmezse:

```bash
php artisan auth-module:install
```

## 🎯 Multi-Tenant Desteği

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

## 📚 Dokümantasyon

- [Otomatik Kurulum](AUTO_INSTALL.md)
- [Social Login Kullanımı](SOCIAL_LOGIN_KULLANIM.md) (eğer varsa)
- [API Dokümantasyonu](API_DOKUMANTASYON.md) (eğer varsa)

## 📝 Gereksinimler

- PHP >= 8.2
- Laravel >= 12.0
- Laravel Sanctum >= 4.0 (Sadece API kullanımı için - opsiyonel)
- Laravel Socialite >= 5.0 (Sadece Social Login için - opsiyonel)

## 📝 Lisans

MIT

## 👤 Yazar

**Abdullah Zubeyir Yıldız**  
GitHub: [@abdullahzubeyiryildiz](https://github.com/abdullahzubeyiryildiz)

## 🤝 Katkıda Bulunma

Pull request'ler memnuniyetle karşılanır. Büyük değişiklikler için lütfen önce bir issue açarak neyi değiştirmek istediğinizi tartışın.
