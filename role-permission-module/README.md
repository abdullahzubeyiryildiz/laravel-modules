# Role Permission Module

Dinamik rol ve izin yönetimi modülü. auth-module ve rbac-module tarafından opsiyonel olarak kullanılabilir.

## Özellikler

- **Dinamik Roller**: Veritabanından yönetilen roller (admin, manager, user veya özel roller)
- **Dinamik İzinler**: Gruplar halinde izin tanımları
- **Çoklu Kiracı Desteği**: Tenant bazlı rol/izin (opsiyonel)
- **Cache Desteği**: Rol/izin kontrolleri cache'lenebilir
- **Legacy Uyumluluk**: `users.role` kolonu ve `tenant_users` tablosu ile fallback

## Kurulum

1. `composer.json`'a ekleyin: `"modules/role-permission-module": "^1.0.0"`
2. `composer update` çalıştırın
3. `php artisan migrate` ile tabloları oluşturun
4. Varsayılan roller için: `php artisan role-permission:seed`

## Kullanım

### Rolleri Seed Etme

```php
app(\Modules\RolePermissionModule\Services\RolePermissionService::class)->seedDefaults($tenantId);
```

### Rol Kontrolü

```php
$service = app(\Modules\RolePermissionModule\Services\RolePermissionService::class);

$service->hasRole($user, 'admin');
$service->hasRole($user, ['admin', 'manager']);
$service->hasPermission($user, 'users.edit');
$service->isAdmin($user);
$service->getPrimaryRole($user);
```

### User Model'e HasRoles Trait

```php
use Modules\RolePermissionModule\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;

    // $user->hasRole('admin')
    // $user->hasPermission('users.edit')
    // $user->assignRole('manager')
    // $user->syncRoles(['user', 'editor'])
}
```

### Helper Kullanımı

```php
use Modules\RolePermissionModule\Helpers\RolePermissionHelper;

RolePermissionHelper::hasRole($user, 'admin');
RolePermissionHelper::hasPermission($user, 'users.edit');
RolePermissionHelper::isAdmin($user);
```

## Konfigürasyon

`config/role-permission-module.php` veya `.env`:

- `ROLE_PERMISSION_ENABLED` - Modül aktif mi (varsayılan: true)
- `ROLE_PERMISSION_DEFAULT_ROLE` - Yeni kullanıcı varsayılan rolü (varsayılan: user)
- `ROLE_PERMISSION_SUPER_ADMIN` - Tüm izinlere sahip rol (varsayılan: admin)
- `ROLE_PERMISSION_MULTI_TENANT` - Çoklu kiracı aktif mi

## auth-module ve rbac-module ile Entegrasyon

- **auth-module**: Kayıt sırasında varsayılan rol atanır, API response'larında rol gösterilir
- **rbac-module**: PermissionService rol/izin kontrollerini bu modüle devreder
- **user-management-module**: Dinamik rollerle kullanıcı oluşturma/düzenleme
