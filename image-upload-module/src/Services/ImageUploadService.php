<?php

namespace Modules\ImageUploadModule\Services;

use Modules\ImageUploadModule\Contracts\ImageUploadServiceInterface;
use Modules\ImageUploadModule\Exceptions\ImageUploadException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadService implements ImageUploadServiceInterface
{
    /**
     * Dosya yükleme (Resim, SVG, PDF)
     *
     * @param UploadedFile $file
     * @param string $folder
     * @param array $options
     * @return array
     */
    public function upload(UploadedFile $file, string $folder = 'uploads', array $options = []): array
    {
        $disk = $options['disk'] ?? config('image-upload-module.default_disk', 'public');
        $maxSize = $options['max_size'] ?? config('image-upload-module.max_file_size', 5120); // KB
        $allowedMimes = $options['allowed_mimes'] ?? config('image-upload-module.allowed_mimes', [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml', 'application/pdf'
        ]);
        $resize = $options['resize'] ?? config('image-upload-module.resize.enabled', false);
        $width = $options['width'] ?? config('image-upload-module.resize.width', 800);
        $height = $options['height'] ?? config('image-upload-module.resize.height', 800);
        $quality = $options['quality'] ?? config('image-upload-module.quality', 90);
        $generateThumbnail = $options['thumbnail'] ?? config('image-upload-module.thumbnail.enabled', false);
        $thumbnailWidth = $options['thumbnail_width'] ?? config('image-upload-module.thumbnail.width', 200);
        $thumbnailHeight = $options['thumbnail_height'] ?? config('image-upload-module.thumbnail.height', 200);

        // Dosya validasyonu
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new ImageUploadException('Geçersiz dosya tipi. İzin verilen tipler: ' . implode(', ', $allowedMimes));
        }

        if ($file->getSize() > $maxSize * 1024) {
            throw new ImageUploadException('Dosya boyutu çok büyük. Maksimum: ' . $maxSize . ' KB');
        }

        // Dosya tipini belirle
        $mimeType = $file->getMimeType();
        $fileTypeGroups = config('image-upload-module.file_type_groups', []);
        $isImage = in_array($mimeType, $fileTypeGroups['images'] ?? []);
        $isSvg = $mimeType === 'image/svg+xml';
        $isPdf = $mimeType === 'application/pdf';

        // Dosya adı oluştur
        $fileName = $this->generateFileName($file, $options['prefix'] ?? null);
        $filePath = $folder . '/' . $fileName;

        // SVG ve PDF için özel işleme (resize yapılmaz)
        if ($isSvg || $isPdf) {
            // SVG ve PDF direkt kaydedilir, resize yapılmaz
            Storage::disk($disk)->putFileAs($folder, $file, $fileName);
        }
        // Resim işleme (Intervention Image varsa kullan, yoksa direkt kaydet)
        elseif ($isImage && ($resize || $generateThumbnail) && class_exists('Intervention\Image\Facades\Image')) {
            $image = \Intervention\Image\Facades\Image::make($file);

            // Ana resmi kaydet
            if ($resize) {
                $image->resize($width, $height, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }

            // Storage'a kaydet
            $imageData = $image->encode($file->getClientOriginalExtension(), $quality);
            Storage::disk($disk)->put($filePath, $imageData);

            // Thumbnail oluştur (sadece resimler için)
            if ($generateThumbnail) {
                $thumbnailPath = $folder . '/thumbnails/' . $fileName;
                $thumbnail = \Intervention\Image\Facades\Image::make($file);
                $thumbnail->fit($thumbnailWidth, $thumbnailHeight);
                $thumbnailData = $thumbnail->encode($file->getClientOriginalExtension(), $quality);
                Storage::disk($disk)->put($thumbnailPath, $thumbnailData);
            }
        } else {
            // Resim işleme yok veya Intervention Image yüklü değil, direkt kaydet
            Storage::disk($disk)->putFileAs($folder, $file, $fileName);
        }

        // URL oluştur
        $url = $this->getUrl($filePath, $disk);
        $thumbnailUrl = null;

        // Thumbnail sadece resimler için oluşturulur (SVG ve PDF için değil)
        if ($generateThumbnail && $isImage) {
            $thumbnailPath = $folder . '/thumbnails/' . $fileName;
            $thumbnailUrl = $this->getUrl($thumbnailPath, $disk);
        }

        return [
            'path' => $filePath,
            'url' => $url,
            'thumbnail_url' => $thumbnailUrl,
            'filename' => $fileName,
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'file_type' => $this->getFileType($mimeType), // 'image', 'svg', 'pdf'
            'disk' => $disk,
        ];
    }

    /**
     * Resim silme
     *
     * @param string $path
     * @param string|null $disk
     * @return bool
     */
    public function delete(string $path, ?string $disk = null): bool
    {
        $disk = $disk ?? config('image-upload-module.default_disk', 'public');

        // Ana resmi sil
        $deleted = Storage::disk($disk)->delete($path);

        // Thumbnail varsa onu da sil
        $thumbnailPath = dirname($path) . '/thumbnails/' . basename($path);
        if (Storage::disk($disk)->exists($thumbnailPath)) {
            Storage::disk($disk)->delete($thumbnailPath);
        }

        return $deleted;
    }

    /**
     * Eski resmi sil ve yenisini yükle
     *
     * @param UploadedFile $file
     * @param string|null $oldPath
     * @param string $folder
     * @param array $options
     * @return array
     */
    public function replace(UploadedFile $file, ?string $oldPath, string $folder = 'uploads', array $options = []): array
    {
        // Eski resmi sil
        if ($oldPath) {
            $oldDisk = $options['old_disk'] ?? config('image-upload-module.default_disk', 'public');
            $this->delete($oldPath, $oldDisk);
        }

        // Yeni resmi yükle
        return $this->upload($file, $folder, $options);
    }

    /**
     * Dosya adı oluştur
     *
     * @param UploadedFile $file
     * @param string|null $prefix
     * @return string
     */
    protected function generateFileName(UploadedFile $file, ?string $prefix = null): string
    {
        $extension = $file->getClientOriginalExtension();
        $name = Str::random(40);

        if ($prefix) {
            return $prefix . '_' . $name . '.' . $extension;
        }

        return $name . '.' . $extension;
    }

    /**
     * URL oluştur
     *
     * @param string $path
     * @param string $disk
     * @return string
     */
    protected function getUrl(string $path, string $disk): string
    {
        $storage = Storage::disk($disk);

        // URL metodu varsa kullan
        if (method_exists($storage, 'url')) {
            return $storage->url($path);
        }

        // Fallback: public disk için asset kullan
        if ($disk === 'public') {
            return asset('storage/' . $path);
        }

        // S3 için
        if ($disk === 's3' || $disk === 's3_public') {
            $bucket = config("filesystems.disks.{$disk}.bucket");
            $region = config("filesystems.disks.{$disk}.region", 'us-east-1');
            return "https://{$bucket}.s3.{$region}.amazonaws.com/{$path}";
        }

        // Diğer durumlar için path döndür
        return $path;
    }

    /**
     * Dosya tipini belirle
     * Resimler (JPEG, PNG, GIF, WebP, SVG) için 'image' döner
     *
     * @param string $mimeType
     * @return string
     */
    protected function getFileType(string $mimeType): string
    {
        // Tüm resim tipleri için 'image' döndür (SVG dahil)
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }

        if ($mimeType === 'application/pdf') {
            return 'pdf';
        }

        return 'other';
    }
}
