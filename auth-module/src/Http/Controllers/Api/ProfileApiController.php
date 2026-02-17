<?php

namespace Modules\AuthModule\Http\Controllers\Api;

use Modules\AuthModule\Http\Controllers\Controller;
use Modules\FileManagerModule\Services\FileManagerService;
use Modules\FileManagerModule\Models\File as FileModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ProfileApiController extends Controller
{
    protected $fileManagerService;

    public function __construct(FileManagerService $fileManagerService)
    {
        $this->fileManagerService = $fileManagerService;
    }


    /**
     * Profil bilgilerini getir
     */
    public function show(Request $request)
    {
        $user = $request->user();

        $bio = $user->profileDetail?->bio ?? null;

        return $this->response([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar ?? null,
                'avatar_url' => $this->getAvatarUrl($user),
                'phone' => $user->phone ?? null,
                'bio' => $bio,
                'role' => class_exists(\Modules\AuthModule\Helpers\RoleHelper::class) ? \Modules\AuthModule\Helpers\RoleHelper::getRole($user) : null,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ],
        ], __('Profile information'), 200);
    }

    /**
     * Profil bilgilerini güncelle
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->response(
                null,
                __('Validation failed'),
                422,
                $validator->errors()
            );
        }

        $userModel = get_class($user);
        $updateData = [];

        if ($request->has('name')) {
            $updateData['name'] = $request->name;
        }

        if ($request->has('email')) {
            $updateData['email'] = $request->email;
        }

        if ($request->has('phone')) {
            $updateData['phone'] = $request->phone;
        }

        $user->update($updateData);

        if ($request->has('bio')) {
            $user->profileDetail()->updateOrCreate(
                ['user_id' => $user->id],
                ['bio' => $request->bio]
            );
        }

        $user->refresh();
        $user->load('profileDetail');

        $bio = $user->profileDetail?->bio ?? null;

        return $this->response([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar ?? null,
                'avatar_url' => $this->getAvatarUrl($user),
                'phone' => $user->phone ?? null,
                'bio' => $bio,
                'role' => class_exists(\Modules\AuthModule\Helpers\RoleHelper::class) ? \Modules\AuthModule\Helpers\RoleHelper::getRole($user) : null,
            ],
        ], __('Profile updated successfully.'), 200);
    }

    /**
     * Profil resmini güncelle
     */
    public function updateAvatar(Request $request)
    {
        $maxSize = config('file-manager-module.security.max_file_size_mb', 100) * 1024; // KB
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|file|mimes:jpeg,png,jpg,gif,webp,svg|max:' . $maxSize,
        ]);

        if ($validator->fails()) {
            return $this->response(
                null,
                __('Validation failed'),
                422,
                $validator->errors()
            );
        }

        $user = $request->user();

        try {
            // Eski avatar dosyasını bul ve sil
            if ($user->avatar) {
                $oldFile = FileModel::where('path', $user->avatar)
                    ->orWhere('id', $user->avatar) // Eğer avatar file_id ise
                    ->first();

                if ($oldFile) {
                    $this->fileManagerService->delete($oldFile);
                }
            }

            // Yeni avatar'ı FileManagerService ile yükle
            $file = $this->fileManagerService->upload(
                $request->file('avatar'),
                null, // tenant_id (otomatik)
                $user->id, // owner_user_id
                'User', // related_entity
                $user->id, // related_entity_id
                true, // is_private
                'avatars' // folder
            );

            // Avatar'ı file_id veya path olarak kaydet
            // Eğer users tablosunda avatar kolonu file_id tutuyorsa: $user->avatar = $file->id;
            // Eğer path tutuyorsa: $user->avatar = $file->path;
            $user->avatar = $file->path; // veya $file->id
            $user->save();

            return $this->response([
                'avatar' => $file->path,
                'avatar_url' => $file->is_private ? $file->getSignedUrl() : $file->getPublicUrl(),
                'file_id' => $file->id,
            ], __('Profile picture updated successfully.'), 200);

        } catch (\Exception $e) {
            return $this->response(
                null,
                __('Image upload error: :error', ['error' => $e->getMessage()]),
                500
            );
        }
    }

    /**
     * Profil resmini sil
     */
    public function deleteAvatar(Request $request)
    {
        $user = $request->user();

        if (!$user->avatar) {
            return $this->response(
                null,
                __('Profile picture not found.'),
                404
            );
        }

        try {
            // Avatar dosyasını bul
            $file = FileModel::where('path', $user->avatar)
                ->orWhere('id', $user->avatar) // Eğer avatar file_id ise
                ->first();

            if ($file) {
                $this->fileManagerService->delete($file);
            }

            $user->avatar = null;
            $user->save();

            return $this->response(
                null,
                __('Profile picture deleted successfully.'),
                200
            );

        } catch (\Exception $e) {
            return $this->response(
                null,
                __('Image deletion error: :error', ['error' => $e->getMessage()]),
                500
            );
        }
    }

    /**
     * Şifre değiştir
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:' . config('auth-module.validation.password_min_length', 6) . '|confirmed',
        ], [
            'current_password.required' => __('The current password field is required.'),
            'password.required' => __('The new password field is required.'),
            'password.min' => __('The new password must be at least :min characters.', ['min' => config('auth-module.validation.password_min_length', 6)]),
            'password.confirmed' => __('The password confirmation does not match.'),
        ]);

        if ($validator->fails()) {
            return $this->response(
                null,
                __('Validation failed'),
                422,
                $validator->errors()
            );
        }

        $user = $request->user();

        // Mevcut şifre kontrolü
        if (!Hash::check($request->current_password, $user->password)) {
            return $this->response(
                null,
                __('Current password is incorrect.'),
                422,
                ['current_password' => [__('Current password is incorrect.')]]
            );
        }

        // Yeni şifreyi kaydet
        $user->password = Hash::make($request->password);
        $user->save();

        return $this->response(
            null,
            __('Your password has been successfully changed.'),
            200
        );
    }

    /**
     * Avatar URL'ini oluştur
     */
    protected function getAvatarUrl($user): ?string
    {
        if (!$user || !$user->avatar) {
            return null;
        }

        // Avatar file_id veya path olabilir
        $file = FileModel::where('path', $user->avatar)
            ->orWhere('id', $user->avatar)
            ->first();

        if (!$file) {
            return null;
        }

        // Private ise signed URL, public ise public URL
        return $file->is_private
            ? $file->getSignedUrl(60) // 1 saat geçerli
            : $file->getPublicUrl();
    }
}
