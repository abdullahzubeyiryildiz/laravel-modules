# Laravel Modules

Modüler Laravel paketleri koleksiyonu. Her modül bağımsız olarak kullanılabilir.

## 📦 Modüller

### 🔐 Auth Module
**Paket Adı:** `modules/auth-module`  
**Açıklama:** Laravel authentication modülü (login, register, password reset, social login)  
**GitHub:** [auth-module](auth-module/)

**Özellikler:**
- ✅ Login, Register, Password Reset
- ✅ Social Login (Google, Facebook)
- ✅ Multi-tenant desteği (opsiyonel)
- ✅ Web ve API endpoint'leri
- ✅ Next.js entegrasyonu
- ✅ Otomatik trait kurulumu (HasSocialAccounts, HasTenantAndRole)

**Kurulum:**
```bash
composer require modules/auth-module:dev-main
php artisan migrate
```

---

### 📁 File Manager Module
**Paket Adı:** `modules/file-manager-module`  
**Açıklama:** Profesyonel dosya yönetimi modülü (S3, signed URLs, audit logs)  
**GitHub:** [file-manager-module](https://github.com/abdullahzubeyiryildiz/laravel-modules/tree/master/file-manager-module)

**Özellikler:**
- ✅ S3/R2/MinIO desteği
- ✅ Signed URLs (private dosyalar için)
- ✅ Dosya deduplication
- ✅ Tenant bazlı izolasyon
- ✅ Audit logging (RBAC ile)
- ✅ Image SEO (alt_text, width, height)

**Kurulum:**
```bash
composer require modules/file-manager-module:dev-main
php artisan migrate
```

---

### 🖼️ Image Upload Module
**Paket Adı:** `modules/image-upload-module`  
**Açıklama:** Resim yükleme ve işleme modülü (resize, thumbnail, SVG, PDF)  
**GitHub:** [image-upload-module](https://github.com/abdullahzubeyiryildiz/laravel-modules/tree/master/image-upload-module)

**Özellikler:**
- ✅ Resim resize ve thumbnail
- ✅ S3 ve public storage desteği
- ✅ SVG ve PDF desteği
- ✅ Intervention Image entegrasyonu

**Kurulum:**
```bash
composer require modules/image-upload-module:dev-main
```

---

### 🔔 Notification Module
**Paket Adı:** `modules/notification-module`  
**Açıklama:** Bildirim modülü (Mail, SMS, Database notifications)  
**GitHub:** [notification-module](https://github.com/abdullahzubeyiryildiz/laravel-modules/tree/master/notification-module)

**Özellikler:**
- ✅ Mail gönderimi (SMTP, Mailgun)
- ✅ SMS gönderimi (Mutlucell, vb.)
- ✅ Database notifications (Laravel'in built-in sistemi)
- ✅ Çoklu provider desteği
- ✅ Extensible yapı

**Kurulum:**
```bash
composer require modules/notification-module:dev-main
php artisan migrate
```

---

### 🔐 RBAC Module (Opsiyonel)
**Paket Adı:** `modules/rbac-module`  
**Açıklama:** Rol ve yetki yönetimi modülü (Role-Based Access Control)  
**GitHub:** [rbac-module](https://github.com/abdullahzubeyiryildiz/laravel-modules/tree/master/rbac-module)

**Özellikler:**
- ✅ Rol ve yetki yönetimi
- ✅ Audit logging
- ✅ Tenant bazlı izolasyon
- ✅ Permission kontrolü

**Kurulum:**
```bash
composer require modules/rbac-module:dev-main
php artisan migrate
```

**Not:** Bu modül opsiyoneldir. Diğer modüller bu modül olmadan da çalışır.

---

## 🚀 Hızlı Başlangıç

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

### 2. İstediğiniz Modülleri Kur

```bash
# Sadece auth-module
composer require modules/auth-module:dev-main

# Auth + File Manager
composer require modules/auth-module:dev-main modules/file-manager-module:dev-main

# Tüm modüller (RBAC hariç)
composer require \
    modules/auth-module:dev-main \
    modules/file-manager-module:dev-main \
    modules/image-upload-module:dev-main \
    modules/notification-module:dev-main
```

### 3. Migration'ları Çalıştır

```bash
php artisan migrate
```

---

## 📋 Modül Bağımlılıkları

```
Auth Module
├── File Manager Module (opsiyonel - profil resmi için)
├── Notification Module (opsiyonel - kayıt bildirimleri için)
└── RBAC Module (opsiyonel - gelişmiş rol yönetimi için)

File Manager Module
├── Image Upload Module (opsiyonel - resim işleme için)
└── RBAC Module (opsiyonel - audit logs için)

Notification Module
└── Bağımsız

Image Upload Module
└── Bağımsız

RBAC Module
└── Bağımsız (opsiyonel)
```

---

## 🔧 Özellikler

- ✅ **Modüler Yapı:** Her modül bağımsız kullanılabilir
- ✅ **Opsiyonel Bağımlılıklar:** RBAC gibi modüller opsiyonel
- ✅ **Otomatik Kurulum:** Auth Module otomatik olarak User model'ine trait'ler ekler
- ✅ **Clean Code:** Interface/Contract pattern, SOLID principles
- ✅ **API Desteği:** Tüm modüller Web ve API endpoint'leri sağlar
- ✅ **Multi-tenant:** Multi-tenant desteği (opsiyonel)

---

## 📚 Dokümantasyon

Her modülün kendi README.md dosyası vardır:
- [Auth Module README](https://github.com/abdullahzubeyiryildiz/laravel-modules/blob/master/auth-module/README.md)
- [File Manager Module README](https://github.com/abdullahzubeyiryildiz/laravel-modules/blob/master/file-manager-module/README.md)
- [Image Upload Module README](https://github.com/abdullahzubeyiryildiz/laravel-modules/blob/master/image-upload-module/README.md)
- [Notification Module README](https://github.com/abdullahzubeyiryildiz/laravel-modules/blob/master/notification-module/README.md)
- [RBAC Module README](https://github.com/abdullahzubeyiryildiz/laravel-modules/blob/master/rbac-module/README.md)

---

## 📝 Lisans

MIT

---

## 👤 Yazar

**Abdullah Zubeyir Yıldız**  
GitHub: [@abdullahzubeyiryildiz](https://github.com/abdullahzubeyiryildiz)

---

## 🤝 Katkıda Bulunma

Pull request'ler memnuniyetle karşılanır. Büyük değişiklikler için lütfen önce bir issue açarak neyi değiştirmek istediğinizi tartışın.

---

## 📞 Destek

Sorularınız için issue açabilirsiniz.
