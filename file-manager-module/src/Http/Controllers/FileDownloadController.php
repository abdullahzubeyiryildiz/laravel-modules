<?php

namespace Modules\FileManagerModule\Http\Controllers;

use Modules\FileManagerModule\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileDownloadController extends Controller
{
    /**
     * Token ile dosya indir (signed URL için)
     */
    public function download(Request $request, string $token)
    {
        $file = File::where('access_token', $token)
            ->where('is_active', true)
            ->firstOrFail();

        // Token süresi kontrolü (opsiyonel)
        if ($file->expires_at && $file->expires_at->isPast()) {
            abort(410, 'Dosya linki süresi dolmuş.');
        }

        // Audit log
        if (class_exists(\Modules\RbacModule\Services\AuditLogService::class)) {
            $auditService = app(\Modules\RbacModule\Services\AuditLogService::class);
            $auditService->log(
                'downloaded',
                'File',
                $file->id,
                null,
                null,
                ['ip' => $request->ip(), 'user_agent' => $request->userAgent()]
            );
        }

        try {
            $storage = Storage::disk($file->disk);

            if (!$storage->exists($file->path)) {
                abort(404, 'Dosya bulunamadı.');
            }

            // S3 veya remote disk için signed URL kullan
            if (in_array($file->disk, ['s3', 's3_public', 'r2'])) {
                // File model'indeki getSignedUrl metodunu kullan
                $signedUrl = $file->getSignedUrl(5); // 5 dakika geçerli
                if ($signedUrl) {
                    return redirect($signedUrl);
                }
                // Eğer signed URL oluşturulamazsa, public URL'i dene
                $publicUrl = $file->getPublicUrl();
                if ($publicUrl) {
                    return redirect($publicUrl);
                }
                abort(500, 'Dosya URL\'i oluşturulamadı.');
            }

            // Local disk için response()->download() kullan
            $filePath = $storage->path($file->path);
            if (file_exists($filePath)) {
                return response()->download($filePath, $file->original_name);
            }

            abort(404, 'Dosya bulunamadı.');
        } catch (\Exception $e) {
            \Log::error('Dosya indirme hatası: ' . $e->getMessage());
            abort(500, 'Dosya indirilemedi.');
        }
    }
}
