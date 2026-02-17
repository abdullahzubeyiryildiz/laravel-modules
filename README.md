# Laravel Modules

Modüler Laravel paketleri. Her modül bağımsız kullanılabilir.

## Modüller

| Modül | Paket | Özellikler |
|-------|--------|------------|
| **Auth** | `modules/auth-module` | Login, register, password reset · Social login (Google, Facebook) · Profil (avatar, şifre değiştir) · Multi-tenant (opsiyonel) · Web + API route'ları · Next.js uyumu · Otomatik trait'ler: HasSocialAccounts, HasTenantAndRole · Özelleştirilebilir view/route/config |
| **File Manager** | `modules/file-manager-module` | S3/R2/MinIO · Signed URL (private dosyalar) · Dosya deduplication · Tenant izolasyonu · Audit log (RBAC ile opsiyonel) · Image SEO (alt_text, width, height) · Meta bilgileri, soft delete · API: yükleme, listeleme, signed-url, alt-text |
| **Image Upload** | `modules/image-upload-module` | Resize ve thumbnail · S3 + public disk · JPEG, PNG, GIF, WebP, SVG, PDF · Intervention Image (opsiyonel) · Bağımsız, migration yok |
| **Notification** | `modules/notification-module` | Mail (SMTP, Mailgun, SendGrid) · SMS (Mutlucell vb.) · Database notifications (Laravel built-in) · Listeleme, okundu işaretleme · API: gönder, listele, mark-as-read |
| **RBAC** (opsiyonel) | `modules/rbac-module` | Rol ve yetki (roles, permissions, role_permissions) · Audit log servisi · Tenant kullanıcıları (tenant_users) · hasPermission, seedDefaultRolesAndPermissions · Diğer modüller bu modül olmadan çalışır |
| **Role Permission** | `modules/role-permission-module` | Dinamik rol/izin (veritabanından) · HasRoles trait, hasRole/hasPermission · Çoklu tenant (opsiyonel) · Cache · auth-module, rbac-module, user-management ile entegre · `role-permission:seed` |
| **User Management** | `modules/user-management-module` | Kullanıcı CRUD · Admin paneli, DataTable · role-permission-module ile dinamik rol atama |

## Kurulum

**1.** `composer.json` içine repository ekleyin:

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

**2.** Modülleri kurun (örnek):

```bash
composer require modules/auth-module:dev-main
# veya birden fazla: modules/file-manager-module:dev-main vb.
php artisan migrate
```

Migration gerektiren modüller: auth-module, file-manager-module, notification-module, rbac-module, role-permission-module. Sadece `composer require` yeterli: image-upload-module.

## Bağımlılıklar

- **Auth** → File Manager, Notification, RBAC (hepsi opsiyonel)
- **File Manager** → Image Upload, RBAC (opsiyonel)
- **User Management** → Role Permission (rol atama için)
- **Notification, Image Upload, RBAC, Role Permission** → Bağımsız

## Dokümantasyon

Her modülün kendi README’si: [auth-module](auth-module/), [file-manager-module](file-manager-module/), [image-upload-module](image-upload-module/), [notification-module](notification-module/), [rbac-module](rbac-module/), [role-permission-module](role-permission-module/).

---

**Lisans:** MIT · **Yazar:** [Abdullah Zubeyir Yıldız](https://github.com/abdullahzubeyiryildiz)
