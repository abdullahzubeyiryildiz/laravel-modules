# File Manager Module

Profesyonel dosya yönetimi modülü. S3 desteği, signed URLs, audit logs ve tenant izolasyonu ile SaaS uygulamaları için tasarlanmıştır.

## ✨ Özellikler

- ✅ S3/R2/MinIO desteği
- ✅ Signed URLs (private dosyalar için)
- ✅ Dosya deduplication
- ✅ Tenant bazlı izolasyon
- ✅ Audit logging (RBAC ile - opsiyonel)
- ✅ Image SEO (alt_text, width, height)
- ✅ Dosya meta bilgileri
- ✅ Soft delete desteği

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
composer require modules/file-manager-module:dev-main
```

### 3. Migration'ları Çalıştır

```bash
php artisan migrate
```

### 4. S3 Yapılandırması (Opsiyonel)

`.env` dosyasına ekleyin:

```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket
```

## 🚀 Kullanım

### Dosya Yükleme

```php
use Modules\FileManagerModule\Services\FileManagerService;

$fileManager = app(FileManagerService::class);

$file = $fileManager->upload(
    $request->file('file'),
    tenantId: null, // Otomatik
    ownerUserId: auth()->id(),
    relatedEntity: 'User',
    relatedEntityId: auth()->id(),
    isPrivate: true,
    folder: 'documents',
    altText: 'Dosya açıklaması'
);
```

### Dosya URL Alma

```php
// Public dosya
$url = $file->getPublicUrl();

// Private dosya (signed URL)
$url = $file->getSignedUrl(60); // 60 dakika geçerli
```

### Dosya Listeleme

```php
$files = $fileManager->listFiles([
    'tenant_id' => 1,
    'owner_user_id' => auth()->id(),
    'file_type' => 'image',
]);
```

## 📋 API Endpoints

- `POST /api/files` - Dosya yükle
- `GET /api/files` - Dosyaları listele
- `GET /api/files/{id}` - Dosya detayı
- `DELETE /api/files/{id}` - Dosya sil
- `POST /api/files/{id}/signed-url` - Signed URL al
- `PUT /api/files/{id}/alt-text` - Alt text güncelle

## ⚠️ Not

**RBAC modülü opsiyoneldir.** Bu modül RBAC olmadan da çalışır (sadece audit logs olmadan).

## 📝 Gereksinimler

- PHP >= 8.2
- Laravel >= 12.0
- `league/flysystem-aws-s3-v3` (S3 kullanımı için - opsiyonel)
- `modules/image-upload-module` (Resim işleme için - opsiyonel)
- `modules/rbac-module` (Audit logs için - opsiyonel)

## 📝 Lisans

MIT

## 👤 Yazar

**Abdullah Zubeyir Yıldız**  
GitHub: [@abdullahzubeyiryildiz](https://github.com/abdullahzubeyiryildiz)
