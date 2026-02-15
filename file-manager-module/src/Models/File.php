<?php

namespace Modules\FileManagerModule\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class File extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'owner_user_id',
        'disk',
        'bucket',
        'path',
        'url',
        'original_name',
        'alt_text',
        'width',
        'height',
        'mime_type',
        'size_bytes',
        'hash_sha256',
        'extension',
        'is_private',
        'access_token',
        'file_type',
        'meta',
        'related_entity',
        'related_entity_id',
        'is_active',
        'expires_at',
    ];

    protected $casts = [
        'is_private' => 'boolean',
        'is_active' => 'boolean',
        'size_bytes' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'meta' => 'array',
        'expires_at' => 'datetime',
    ];

    /**
     * Tenant ilişkisi
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    /**
     * Owner user ilişkisi
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'owner_user_id');
    }

    /**
     * İlişkili entity (polymorphic)
     */
    public function related(): MorphTo
    {
        return $this->morphTo('related');
    }

    /**
     * Signed URL oluştur (private dosyalar için)
     */
    public function getSignedUrl(?int $expiresInMinutes = 10): ?string
    {
        if (!$this->is_private) {
            return $this->url;
        }

        try {
            $storage = Storage::disk($this->disk);

            if (method_exists($storage, 'temporaryUrl')) {
                return $storage->temporaryUrl($this->path, now()->addMinutes($expiresInMinutes));
            }

            // Fallback: access token kullan
            if (!$this->access_token) {
                $this->access_token = Str::random(64);
                $this->save();
            }

            return route('file.download', ['token' => $this->access_token]);
        } catch (\Exception $e) {
            \Log::error('Signed URL oluşturma hatası: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Public URL (eğer public ise)
     */
    public function getPublicUrl(): ?string
    {
        if ($this->is_private) {
            return null;
        }

        if ($this->url) {
            return $this->url;
        }

        try {
            $storage = Storage::disk($this->disk);
            if (method_exists($storage, 'url')) {
                return $storage->url($this->path);
            }
        } catch (\Exception $e) {
            \Log::error('Public URL oluşturma hatası: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Dosya boyutunu formatla
     */
    public function getFormattedSize(): string
    {
        $bytes = $this->size_bytes;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Dosya tipini belirle (MIME type'dan)
     * Resimler (JPEG, PNG, GIF, WebP, SVG) için 'image' döner
     */
    public static function getFileTypeFromMime(string $mimeType): string
    {
        // Tüm resim tipleri için 'image' döndür (SVG dahil)
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }
        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }
        if (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        }
        if (in_array($mimeType, ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])) {
            return 'document';
        }

        return 'other';
    }

    /**
     * SEO için optimize edilmiş image tag oluştur
     */
    public function toImageTag(array $attributes = []): string
    {
        $defaultAttributes = [
            'src' => $this->is_private ? $this->getSignedUrl(60) : $this->getPublicUrl(),
            'alt' => $this->alt_text ?? $this->original_name,
            'loading' => 'lazy',
        ];

        // Width ve height varsa ekle
        if ($this->width) {
            $defaultAttributes['width'] = $this->width;
        }
        if ($this->height) {
            $defaultAttributes['height'] = $this->height;
        }

        $attributes = array_merge($defaultAttributes, $attributes);

        $html = '<img';
        foreach ($attributes as $key => $value) {
            if ($value !== null) {
                $html .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
            }
        }
        $html .= '>';

        return $html;
    }

    /**
     * SEO meta bilgilerini al
     */
    public function getSeoMeta(): array
    {
        return [
            'alt' => $this->alt_text ?? $this->original_name,
            'url' => $this->is_private ? $this->getSignedUrl(60) : $this->getPublicUrl(),
            'width' => $this->width,
            'height' => $this->height,
        ];
    }

    /**
     * Open Graph meta tag'leri için
     */
    public function getOpenGraphTags(): array
    {
        return [
            'og:image' => $this->is_private ? $this->getSignedUrl(60) : $this->getPublicUrl(),
            'og:image:width' => $this->width,
            'og:image:height' => $this->height,
            'og:image:alt' => $this->alt_text ?? $this->original_name,
        ];
    }
}
