# Image Upload Module

Reusable image upload module for Laravel with S3 and public storage support.

## Özellikler

- ✅ S3 ve Public storage desteği
- ✅ Otomatik resize (resimler için)
- ✅ Thumbnail oluşturma (resimler için)
- ✅ SVG dosya desteği
- ✅ PDF dosya desteği
- ✅ Dosya validasyonu
- ✅ Reusable - başka modüllerde kullanılabilir
- ✅ Intervention Image desteği (opsiyonel)

## Kurulum

### 1. Paketi Yükle

```bash
composer require modules/image-upload-module
```

### 2. Config Yayınla

```bash
php artisan vendor:publish --tag=image-upload-module-config
```

### 3. (Opsiyonel) Intervention Image Kur

Resim işleme için:

```bash
composer require intervention/image
```

## Kullanım

### Resim Yükleme (ImageUploadService)

```php
use Modules\ImageUploadModule\Services\ImageUploadService;

class YourController extends Controller
{
    public function upload(Request $request, ImageUploadService $imageService)
    {
        $result = $imageService->upload(
            $request->file('image'),
            'uploads', // folder
            [
                'disk' => 'public', // veya 's3'
                'resize' => true,
                'width' => 800,
                'height' => 800,
                'thumbnail' => true,
            ]
        );

        return response()->json($result);
    }
}
```

### SVG/PDF Yükleme

```php
use Modules\ImageUploadModule\Services\ImageUploadService;

// SVG veya PDF yükleme (resize yapılmaz)
$result = $imageService->upload(
    $request->file('document'),
    'documents',
    [
        'disk' => 's3',
        // resize ve thumbnail seçenekleri SVG/PDF için göz ardı edilir
    ]
);
```

### Genel Dosya Yükleme

ImageUploadService tüm dosya tiplerini (resim, SVG, PDF) destekler:

```php
use Modules\ImageUploadModule\Services\ImageUploadService;

$fileService = app(ImageUploadService::class);
$result = $fileService->upload(
    $request->file('file'),
    'files',
    ['disk' => 'public']
);
```

### Helper Method

```php
use Modules\ImageUploadModule\Services\ImageUploadService;

$imageService = app(ImageUploadService::class);
$result = $imageService->upload($file, 'avatars');
```

## Yapılandırma

`.env` dosyasına ekleyin:

```env
IMAGE_UPLOAD_DEFAULT_DISK=public
IMAGE_UPLOAD_MAX_SIZE=5120
IMAGE_UPLOAD_RESIZE_ENABLED=true
IMAGE_UPLOAD_RESIZE_WIDTH=800
IMAGE_UPLOAD_RESIZE_HEIGHT=800
IMAGE_UPLOAD_THUMBNAIL_ENABLED=true
IMAGE_UPLOAD_THUMBNAIL_WIDTH=200
IMAGE_UPLOAD_THUMBNAIL_HEIGHT=200
```

## S3 Kullanımı

`config/filesystems.php` dosyasında S3 yapılandırması:

```php
's3' => [
    'driver' => 's3',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION'),
    'bucket' => env('AWS_BUCKET'),
],
```

`.env`:
```env
IMAGE_UPLOAD_DEFAULT_DISK=s3
```

## API

### upload()

```php
$result = $imageService->upload($file, $folder, $options);
```

**Dönen değer:**
```php
[
    'path' => 'avatars/xxxxx.jpg',
    'url' => 'https://...',
    'thumbnail_url' => 'https://...',
    'filename' => 'xxxxx.jpg',
    'original_name' => 'photo.jpg',
    'size' => 12345,
    'mime_type' => 'image/jpeg',
    'disk' => 'public',
]
```

### delete()

```php
$imageService->delete($path, $disk);
```

### replace()

```php
$result = $imageService->replace($newFile, $oldPath, $folder, $options);
```

## Başka Modülde Kullanım

```php
use Modules\ImageUploadModule\Services\ImageUploadService;

class ProductController extends Controller
{
    public function store(Request $request)
    {
        $imageService = app(ImageUploadService::class);
        
        $image = $imageService->upload(
            $request->file('image'),
            'products',
            ['disk' => 's3', 'resize' => true]
        );
        
        Product::create([
            'name' => $request->name,
            'image' => $image['path'],
            'image_url' => $image['url'],
        ]);
    }
}
```

## Lisans

MIT
