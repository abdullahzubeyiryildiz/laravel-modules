<?php

namespace Modules\FileManagerModule\Contracts;

use Modules\FileManagerModule\Models\File;
use Illuminate\Http\UploadedFile;

interface FileManagerServiceInterface
{
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
    ): File;

    /**
     * Dosya sil
     */
    public function delete(File $file, bool $forceDelete = false): bool;

    /**
     * Dosya bilgilerini al
     */
    public function getFile(int $fileId): ?File;

    /**
     * Dosyaları listele
     */
    public function listFiles(array $filters = []): \Illuminate\Database\Eloquent\Collection;
}
