# Auth Module Otomatik Kurulum

Auth Module kurulduğunda User model'ine **otomatik olarak** trait'ler eklenir!

## 🚀 Otomatik Kurulum

### ✅ Tam Otomatik (Önerilen)

Paketi kurduğunuzda **otomatik olarak** trait'ler eklenir:

```bash
composer require modules/auth-module:dev-main
# ✅ Trait'ler otomatik olarak eklenir!
```

**Nasıl çalışır?**
- Composer `post-install-cmd` ve `post-update-cmd` script'leri otomatik çalışır
- User model'i otomatik olarak güncellenir
- Hiçbir manuel işlem gerekmez!

### Yöntem 2: Manuel Command (Gerekirse)

Eğer otomatik eklenmezse:

```bash
php artisan auth-module:install
```

Bu komut:
- ✅ User model'ini bulur
- ✅ `HasSocialAccounts` trait'ini ekler (social accounts için)
- ✅ `HasTenantAndRole` trait'ini ekler (tenant ve role method'ları için)
- ✅ Gerekli use statement'ları ekler
- ✅ Eski manuel method'ları kaldırır (tenant, isAdmin, isManager, socialAccounts)

### Yöntem 2: Manuel Kurulum

Eğer command çalışmazsa, manuel olarak ekleyin:

```php
// app/Models/User.php
use Modules\AuthModule\Traits\HasSocialAccounts;
use Modules\AuthModule\Traits\HasTenantAndRole;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasSocialAccounts, HasTenantAndRole;
    // ...
}
```

## 📋 Trait Özellikleri

### HasSocialAccounts Trait

`HasSocialAccounts` trait'i şu method'ları sağlar:

### 1. socialAccounts()

```php
$user->socialAccounts; // Tüm social account'ları
```

### 2. hasSocialAccount(string $provider)

```php
if ($user->hasSocialAccount('google')) {
    // Google ile bağlı
}
```

### 3. addSocialAccount(string $provider, string $providerId, array $data = [])

```php
$user->addSocialAccount('google', '123456789', [
    'email' => 'user@example.com',
    'name' => 'John Doe',
    'avatar' => 'https://...',
]);
```

### 4. removeSocialAccount(string $provider)

```php
$user->removeSocialAccount('google');
```

### 5. removeAllSocialAccounts()

```php
$user->removeAllSocialAccounts();
```

### HasTenantAndRole Trait

`HasTenantAndRole` trait'i şu method'ları sağlar:

### 1. tenant()

```php
$user->tenant; // Tenant model'i
```

### 2. isAdmin()

```php
if ($user->isAdmin()) {
    // Admin kullanıcı
}
```

### 3. isManager()

```php
if ($user->isManager()) {
    // Admin veya Manager kullanıcı
}
```

## ✅ Kontrol

User model'inizde trait'lerin eklendiğini kontrol edin:

```php
// app/Models/User.php
use Modules\AuthModule\Traits\HasSocialAccounts;
use Modules\AuthModule\Traits\HasTenantAndRole;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasSocialAccounts, HasTenantAndRole; // ✅ Burada olmalı
}
```

## 🔧 Sorun Giderme

### Hata: "Class 'Modules\AuthModule\Traits\HasSocialAccounts' not found"

```bash
composer dump-autoload
php artisan config:clear
php artisan cache:clear
```

### Hata: "Trait already exists"

Trait zaten eklenmiş. Herhangi bir işlem yapmanıza gerek yok.

### Command çalışmıyor

Manuel olarak ekleyin (Yöntem 2).
