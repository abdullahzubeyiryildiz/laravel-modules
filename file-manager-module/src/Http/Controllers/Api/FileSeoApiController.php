<?php

namespace Modules\FileManagerModule\Http\Controllers\Api;

use Modules\FileManagerModule\Http\Controllers\Controller;
use Modules\FileManagerModule\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class FileSeoApiController extends Controller
{

    /**
     * Alt text güncelle
     */
    public function update(Request $request, $id)
    {
        $file = File::findOrFail($id);

        // Yetki kontrolü
        if ($file->owner_user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            return $this->response(
                null,
                'Bu dosyanın SEO bilgilerini güncelleme yetkiniz yok.',
                403
            );
        }

        $validator = Validator::make($request->all(), [
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

        $file->alt_text = $request->alt_text ?? $file->alt_text;
        $file->save();

        return $this->response([
            'file' => [
                'id' => $file->id,
                'alt_text' => $file->alt_text,
                'seo_meta' => $file->getSeoMeta(),
                'open_graph_tags' => $file->getOpenGraphTags(),
            ],
        ], 'Alt text başarıyla güncellendi.', 200);
    }
}
