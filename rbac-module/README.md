# RBAC Module

Role-Based Access Control (RBAC) modülü - **OPSIYONEL**

## 📋 Açıklama

Bu modül, gelişmiş rol ve yetki yönetimi için kullanılır. **Her projede kullanılması zorunlu değildir.**

## ⚠️ Önemli Notlar

- ✅ **Opsiyonel Modül**: Diğer modüller bu modül olmadan da çalışır
- ✅ **Auth Module**: RBAC'a bağımlı değil (kendi `RoleService`'i var)
- ✅ **File Manager Module**: RBAC sadece audit logs için opsiyonel kullanır

## 🚀 Kurulum

### Composer ile Kurulum

```bash
composer require modules/rbac-module:dev-main
```

### Migration'ları Çalıştır

```bash
php artisan migrate
```

### Config Yayınla

```bash
php artisan vendor:publish --tag=rbac-module-config
```

## 📦 Ne Zaman Kullanılır?

### ✅ Kullanın Eğer:

- Gelişmiş permission sistemi gerekiyorsa
- Audit logs tutmak istiyorsanız
- Rol-yetki ilişkileri yönetmek istiyorsanız
- Multi-tenant SaaS projesi geliştiriyorsanız

### ❌ Kullanmayın Eğer:

- Basit proje geliştiriyorsanız
- Sadece basit rol kontrolü yeterliyse (Auth Module'ün kendi RoleService'i yeterli)
- Audit logs gerekmiyorsa

## 💻 Kullanım

### Varsayılan Rolleri Oluştur

```php
use Modules\RbacModule\Services\PermissionService;

$permissionService = app(PermissionService::class);
$permissionService->seedDefaultRolesAndPermissions($tenantId);
```

### Permission Kontrolü

```php
use Modules\RbacModule\Services\PermissionService;

$permissionService = app(PermissionService::class);

if ($permissionService->hasPermission(Auth::user(), 'users.edit')) {
    // Kullanıcı düzenleme yetkisi var
}
```

### Audit Log

```php
use Modules\RbacModule\Services\AuditLogService;

$auditService = app(AuditLogService::class);
$auditService->log('uploaded', 'File', $file->id, [
    'file_name' => $file->original_name,
    'size' => $file->size_bytes,
]);
```

## 🔗 Diğer Modüllerle Entegrasyon

### File Manager Module

File Manager Module, RBAC'ı sadece audit logs için opsiyonel kullanır:

```php
// RBAC varsa audit log yapılır
if (class_exists(\Modules\RbacModule\Services\AuditLogService::class)) {
    $auditService = app(\Modules\RbacModule\Services\AuditLogService::class);
    $auditService->log('uploaded', 'File', $file->id);
}
```

**RBAC yoksa:** Dosya yükleme çalışır, sadece audit log yapılmaz.

## 📚 Dokümantasyon

Detaylı kullanım için:
- `RBAC_OPSIYONEL_KULLANIM.md` - Opsiyonel kullanım rehberi
- `SaaS_VERITABANI_DOKUMANTASYON.md` - RBAC sistemi detayları

## 📝 Lisans

MIT
