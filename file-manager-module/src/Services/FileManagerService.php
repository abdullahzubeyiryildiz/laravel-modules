<?php

namespace Modules\FileManagerModule\Services;

use Modules\FileManagerModule\Contracts\FileManagerServiceInterface;
use Modules\FileManagerModule\Models\File;
use Modules\FileManagerModule\Exceptions\FileManagerException;
use Modules\ImageUploadModule\Contracts\ImageUploadServiceInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class FileManagerService implements FileManagerServiceInterface
{
    protected ?ImageUploadServiceInterface $imageUploadService;

    public function __construct(?ImageUploadServiceInterface $imageUploadService = null)
    {
        $this->imageUploadService = $imageUploadService;
    }

    /**
     * Dosya yükle
     */
    public function upload(
        UploadedFile $file,
        ?int $tenantId = null,
        ?int $ownerUserId = null,
        ?string $relatedEntity = null,
        ?int $relatedEntityId = null,
        bool $isPrivate = true,
        ?string $folder = null,
        ?string $altText = null
    ): File {
        $tenantId = $tenantId ?? $this->getTenantId();

        // Dosya hash'i hesapla (deduplication için)
        $hash = hash_file('sha256', $file->getRealPath());

        // Aynı dosya var mı kontrol et (deduplication)
        $existingFile = File::where('tenant_id', $tenantId)
            ->where('hash_sha256', $hash)
            ->where('is_active', true)
            ->first();

        if ($existingFile && config('file-manager-module.deduplication.enabled', true)) {
            // Mevcut dosyayı kullan, yeni kayıt oluştur
            $newFile = $existingFile->replicate();
            $newFile->owner_user_id = $ownerUserId;
            $newFile->related_entity = $relatedEntity;
            $newFile->related_entity_id = $relatedEntityId;
            $newFile->access_token = null; // Yeni token oluşturulacak
            $newFile->save();
            return $newFile;
        }

        // Dosya yolunu oluştur
        $folder = $folder ?? $this->generateFolderPath($tenantId);
        $fileName = $this->generateFileName($file);
        $filePath = $folder . '/' . $fileName;

        // Storage disk'i
        $disk = config('file-manager-module.default_disk', 's3');

        $uploadResult = null;
        $meta = $this->extractMeta($file);

        // Resim ise ImageUploadService kullan (resize ile)
        if ($this->imageUploadService && str_starts_with($file->getMimeType(), 'image/')) {
            $uploadResult = $this->imageUploadService->upload($file, $folder, [
                'disk' => $disk,
                'resize' => config('file-manager-module.image.resize', false),
                'width' => config('file-manager-module.image.max_width', 2000),
                'height' => config('file-manager-module.image.max_height', 2000),
                'quality' => config('image-upload-module.quality', 90),
                'thumbnail' => config('image-upload-module.thumbnail.enabled', false),
                'thumbnail_width' => config('image-upload-module.thumbnail.width', 200),
                'thumbnail_height' => config('image-upload-module.thumbnail.height', 200),
            ]);
            $filePath = $uploadResult['path'];

            // Thumbnail bilgisi varsa meta'ya ekle
            if (isset($uploadResult['thumbnail_url'])) {
                $meta['thumbnail_path'] = $uploadResult['thumbnail_path'] ?? null;
                $meta['thumbnail_url'] = $uploadResult['thumbnail_url'];
            }
        } else {
            // Diğer dosyalar için direkt yükle
            Storage::disk($disk)->putFileAs($folder, $file, $fileName);
        }

        // File kaydı oluştur
        $fileModel = File::create([
            'tenant_id' => $tenantId,
            'owner_user_id' => $ownerUserId,
            'disk' => $disk,
            'bucket' => config("filesystems.disks.{$disk}.bucket"),
            'path' => $filePath,
            'url' => $isPrivate ? null : $this->getPublicUrl($filePath, $disk),
            'original_name' => $file->getClientOriginalName(),
            'alt_text' => $altText,
            'width' => $meta['width'] ?? null,
            'height' => $meta['height'] ?? null,
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'hash_sha256' => $hash,
            'extension' => $file->getClientOriginalExtension(),
            'is_private' => $isPrivate,
            'file_type' => File::getFileTypeFromMime($file->getMimeType()),
            'meta' => $meta,
            'related_entity' => $relatedEntity,
            'related_entity_id' => $relatedEntityId,
            'is_active' => true,
        ]);

        // Audit log
        $this->logFileUpload($fileModel);

        return $fileModel;
    }

    /**
     * Dosya sil
     */
    public function delete(File $file, bool $forceDelete = false): bool
    {
        try {
            // Storage'dan sil
            Storage::disk($file->disk)->delete($file->path);

            // Thumbnail varsa onu da sil
            if ($file->meta && isset($file->meta['thumbnail_path'])) {
                Storage::disk($file->disk)->delete($file->meta['thumbnail_path']);
            }

            // Audit log
            $this->logFileDelete($file);

            if ($forceDelete) {
                $file->forceDelete();
            } else {
                $file->delete();
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('Dosya silme hatası: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Dosya bilgilerini al
     */
    public function getFile(int $fileId): ?File
    {
        return File::find($fileId);
    }

    /**
     * Dosyaları listele
     */
    public function listFiles(array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = File::query();

        if (isset($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        if (isset($filters['owner_user_id'])) {
            $query->where('owner_user_id', $filters['owner_user_id']);
        }

        if (isset($filters['file_type'])) {
            $query->where('file_type', $filters['file_type']);
        }

        if (isset($filters['is_private'])) {
            $query->where('is_private', $filters['is_private']);
        }

        if (isset($filters['related_entity'])) {
            $query->where('related_entity', $filters['related_entity']);
        }

        if (isset($filters['related_entity_id'])) {
            $query->where('related_entity_id', $filters['related_entity_id']);
        }

        return $query->get();
    }

    /**
     * Dosya yolunu oluştur (tenant bazlı, tarih bazlı)
     */
    protected function generateFolderPath(?int $tenantId): string
    {
        $year = now()->year;
        $month = now()->format('m');

        if ($tenantId) {
            return "t/{$tenantId}/{$year}/{$month}";
        }

        return "uploads/{$year}/{$month}";
    }

    /**
     * Dosya adı oluştur
     */
    protected function generateFileName(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $uuid = Str::uuid()->toString();
        return "{$uuid}.{$extension}";
    }

    /**
     * Public URL oluştur
     */
    protected function getPublicUrl(string $path, string $disk): ?string
    {
        try {
            $storage = Storage::disk($disk);
            if (method_exists($storage, 'url')) {
                return $storage->url($path);
            }
        } catch (\Exception $e) {
            return null;
        }
        return null;
    }

    /**
     * Dosya meta bilgilerini çıkar
     */
    protected function extractMeta(UploadedFile $file): array
    {
        $meta = [];

        // Resim için
        if (str_starts_with($file->getMimeType(), 'image/')) {
            try {
                $imageInfo = getimagesize($file->getRealPath());
                if ($imageInfo) {
                    $meta['width'] = $imageInfo[0];
                    $meta['height'] = $imageInfo[1];
                }
            } catch (\Exception $e) {
                // Hata durumunda devam et
            }
        }

        return $meta;
    }

    /**
     * Tenant ID'yi al
     */
    protected function getTenantId(): ?int
    {
        if (config('auth-module.multi_tenant.enabled', false)) {
            $tenantHelperClass = config('auth-module.multi_tenant.tenant_helper_class', 'App\Helpers\TenantHelper');
            if (class_exists($tenantHelperClass) && method_exists($tenantHelperClass, 'id')) {
                return $tenantHelperClass::id();
            }
        }

        return null;
    }

    /**
     * File upload audit log
     */
    protected function logFileUpload(File $file): void
    {
        if (class_exists(\Modules\RbacModule\Services\AuditLogService::class)) {
            $auditService = app(\Modules\RbacModule\Services\AuditLogService::class);
            $auditService->log(
                'uploaded',
                'File',
                $file->id,
                null,
                ['path' => $file->path, 'size' => $file->size_bytes, 'type' => $file->file_type],
                ['file_name' => $file->original_name]
            );
        }
    }

    /**
     * File delete audit log
     */
    protected function logFileDelete(File $file): void
    {
        if (class_exists(\Modules\RbacModule\Services\AuditLogService::class)) {
            $auditService = app(\Modules\RbacModule\Services\AuditLogService::class);
            $auditService->log(
                'deleted',
                'File',
                $file->id,
                ['path' => $file->path, 'size' => $file->size_bytes],
                null,
                ['file_name' => $file->original_name]
            );
        }
    }
}
