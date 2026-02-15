<?php

namespace Modules\FileManagerModule\Http\Controllers\Api;

use Modules\FileManagerModule\Http\Controllers\Controller;
use Modules\FileManagerModule\Services\FileManagerService;
use Modules\FileManagerModule\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class FileApiController extends Controller
{
    protected FileManagerService $fileManagerService;

    public function __construct(FileManagerService $fileManagerService)
    {
        $this->fileManagerService = $fileManagerService;
    }

    /**
     * Dosya yükle
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:' . (config('file-manager-module.security.max_file_size_mb', 100) * 1024),
            'is_private' => 'boolean',
            'related_entity' => 'nullable|string',
            'related_entity_id' => 'nullable|integer',
            'alt_text' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->response(
                null,
                'Validation failed',
                422,
                $validator->errors()
            );
        }

        try {
            $file = $this->fileManagerService->upload(
                $request->file('file'),
                null, // tenant_id (otomatik alınır)
                Auth::id(),
                $request->related_entity,
                $request->related_entity_id,
                $request->boolean('is_private', true),
                null, // folder
                $request->alt_text
            );

            return $this->response([
                'file' => [
                    'id' => $file->id,
                    'original_name' => $file->original_name,
                    'alt_text' => $file->alt_text,
                    'width' => $file->width,
                    'height' => $file->height,
                    'size' => $file->size_bytes,
                    'size_formatted' => $file->getFormattedSize(),
                    'mime_type' => $file->mime_type,
                    'file_type' => $file->file_type,
                    'url' => $file->is_private ? $file->getSignedUrl() : $file->getPublicUrl(),
                    'is_private' => $file->is_private,
                    'seo_meta' => $file->getSeoMeta(),
                    'open_graph_tags' => $file->getOpenGraphTags(),
                    'created_at' => $file->created_at,
                ],
            ], 'Dosya başarıyla yüklendi.', 201);

        } catch (\Exception $e) {
            return $this->response(
                null,
                'Dosya yükleme hatası: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Dosya listesi
     */
    public function index(Request $request)
    {
        $query = File::query();

        // Filtreler
        if ($request->has('file_type')) {
            $query->where('file_type', $request->file_type);
        }

        if ($request->has('related_entity')) {
            $query->where('related_entity', $request->related_entity);
            if ($request->has('related_entity_id')) {
                $query->where('related_entity_id', $request->related_entity_id);
            }
        }

        if ($request->has('is_private')) {
            $query->where('is_private', $request->boolean('is_private'));
        }

        // Sadece kullanıcının dosyaları
        if ($request->boolean('my_files', false)) {
            $query->where('owner_user_id', Auth::id());
        }

        $files = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return $this->response([
            'files' => $files->map(function ($file) {
                return [
                    'id' => $file->id,
                    'original_name' => $file->original_name,
                    'size' => $file->size_bytes,
                    'size_formatted' => $file->getFormattedSize(),
                    'mime_type' => $file->mime_type,
                    'file_type' => $file->file_type,
                    'url' => $file->is_private ? $file->getSignedUrl() : $file->getPublicUrl(),
                    'is_private' => $file->is_private,
                    'created_at' => $file->created_at,
                ];
            }),
            'pagination' => [
                'current_page' => $files->currentPage(),
                'last_page' => $files->lastPage(),
                'per_page' => $files->perPage(),
                'total' => $files->total(),
            ],
        ], 'Dosyalar listelendi.', 200);
    }

    /**
     * Dosya detayı
     */
    public function show($id)
    {
        $file = File::findOrFail($id);

        // Yetki kontrolü
        if ($file->owner_user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            return $this->response(
                null,
                'Bu dosyaya erişim yetkiniz yok.',
                403
            );
        }

        return $this->response([
            'file' => [
                'id' => $file->id,
                'original_name' => $file->original_name,
                'size' => $file->size_bytes,
                'size_formatted' => $file->getFormattedSize(),
                'mime_type' => $file->mime_type,
                'file_type' => $file->file_type,
                'url' => $file->is_private ? $file->getSignedUrl() : $file->getPublicUrl(),
                'is_private' => $file->is_private,
                'meta' => $file->meta,
                'created_at' => $file->created_at,
            ],
        ], 'Dosya detayı', 200);
    }

    /**
     * Dosya sil
     */
    public function destroy($id)
    {
        $file = File::findOrFail($id);

        // Yetki kontrolü
        if ($file->owner_user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            return $this->response(
                null,
                'Bu dosyayı silme yetkiniz yok.',
                403
            );
        }

        $deleted = $this->fileManagerService->delete($file);

        if ($deleted) {
            return $this->response(
                null,
                'Dosya başarıyla silindi.',
                200
            );
        }

        return $this->response(
            null,
            'Dosya silinirken bir hata oluştu.',
            500
        );
    }

    /**
     * Signed URL oluştur
     */
    public function getSignedUrl($id, Request $request)
    {
        $file = File::findOrFail($id);

        // Yetki kontrolü
        if ($file->owner_user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            return $this->response(
                null,
                'Bu dosyaya erişim yetkiniz yok.',
                403
            );
        }

        $expiresIn = $request->get('expires_in', config('file-manager-module.signed_url.expires_in_minutes', 10));
        $signedUrl = $file->getSignedUrl($expiresIn);

        if (!$signedUrl) {
            return $this->response(
                null,
                'Signed URL oluşturulamadı.',
                500
            );
        }

        return $this->response([
            'url' => $signedUrl,
            'expires_in_minutes' => $expiresIn,
        ], 'Signed URL oluşturuldu.', 200);
    }
}
