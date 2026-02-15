# Image Upload Module

Reusable image upload module for Laravel with S3 and public storage support. Resize, thumbnail, SVG ve PDF desteği ile.

## ✨ Özellikler

- ✅ Resim resize ve thumbnail
- ✅ S3 ve public storage desteği
- ✅ SVG ve PDF desteği
- ✅ Intervention Image entegrasyonu
- ✅ Image SEO (alt_text, width, height)
- ✅ Bağımsız modül (herhangi bir bağımlılık yok)

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
composer require modules/image-upload-module:dev-main
```

### 3. (Opsiyonel) Intervention Image Kur

Resize ve thumbnail özellikleri için:

```bash
composer require intervention/image
```

## 🚀 Kullanım

### Basit Yükleme

```php
use Modules\ImageUploadModule\Services\ImageUploadService;

$imageService = app(ImageUploadService::class);

$result = $imageService->upload(
    $request->file('image'),
    'avatars',
    [
        'disk' => 's3',
        'resize' => true,
        'width' => 800,
        'height' => 600,
        'generateThumbnail' => true,
        'thumbnailWidth' => 200,
        'thumbnailHeight' => 200,
    ]
);

// $result['path'] - Dosya yolu
// $result['url']] - URL
// $result['width'] - Genişlik
// $result['height'] - Yükseklik
```

### Resize ile Yükleme

```php
$result = $imageService->upload(
    $request->file('image'),
    'products',
    [
        'disk' => 'public',
        'resize' => true,
        'width' => 1200,
        'height' => 800,
        'generateThumbnail' => true,
    ]
);
```

## 📋 Desteklenen Formatlar

- ✅ JPEG, PNG, GIF, WebP
- ✅ SVG
- ✅ PDF

## 📝 Gereksinimler

- PHP >= 8.2
- Laravel >= 12.0
- `intervention/image` (Resize/thumbnail için - opsiyonel)
- `league/flysystem-aws-s3-v3` (S3 kullanımı için - opsiyonel)

## 📝 Lisans

MIT

## 👤 Yazar

**Abdullah Zubeyir Yıldız**  
GitHub: [@abdullahzubeyiryildiz](https://github.com/abdullahzubeyiryildiz)
