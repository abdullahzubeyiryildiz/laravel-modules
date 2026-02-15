<?php

namespace Modules\ImageUploadModule\Contracts;

use Illuminate\Http\UploadedFile;

interface ImageUploadServiceInterface
{
    /**
     * Dosya yükleme (Resim, SVG, PDF)
     */
    public function upload(UploadedFile $file, string $folder = 'uploads', array $options = []): array;

    /**
     * Resim silme
     */
    public function delete(string $path, ?string $disk = null): bool;

    /**
     * Eski resmi sil ve yenisini yükle
     */
    public function replace(UploadedFile $file, ?string $oldPath, string $folder = 'uploads', array $options = []): array;
}
