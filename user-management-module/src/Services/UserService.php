<?php

namespace Modules\UserManagementModule\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserService
{
    /**
     * Yeni kullanıcı oluştur
     */
    public function create(array $data): User
    {
        DB::beginTransaction();

        try {
            $userModel = config('user-management-module.user_model', User::class);
            $role = $data['role'] ?? config('role-permission-module.default_role_slug', 'user');
            $tenantId = $data['tenant_id'] ?? null;

            $user = $userModel::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'is_active' => $data['is_active'] ?? true,
                'tenant_id' => $tenantId,
            ]);

            if (config('role-permission-module.enabled', false) && class_exists(\Modules\RolePermissionModule\Services\RolePermissionService::class)) {
                app(\Modules\RolePermissionModule\Services\RolePermissionService::class)->assignRole($user, $role, $tenantId);
            }

            DB::commit();

            return $user;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('UserService::create error', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Kullanıcı güncelle
     */
    public function update(User $user, array $data): User
    {
        DB::beginTransaction();

        try {
            $role = $data['role'] ?? config('role-permission-module.default_role_slug', 'user');
            $tenantId = $user->tenant_id;

            $updateData = [
                'name' => $data['name'],
                'email' => $data['email'],
                'is_active' => $data['is_active'] ?? $user->is_active,
            ];

            if (!empty($data['password'])) {
                $updateData['password'] = Hash::make($data['password']);
            }

            $user->update($updateData);

            if (config('role-permission-module.enabled', false) && class_exists(\Modules\RolePermissionModule\Services\RolePermissionService::class) && isset($data['role'])) {
                app(\Modules\RolePermissionModule\Services\RolePermissionService::class)->syncRoles($user, [$role], $tenantId);
            }

            DB::commit();

            return $user->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('UserService::update error', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Kullanıcı sil
     */
    public function delete(User $user): bool
    {
        DB::beginTransaction();

        try {
            $user->delete();

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('UserService::delete error', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);
            throw $e;
        }
    }

    /**
     * Kullanıcı durumunu değiştir (aktif/pasif)
     */
    public function toggleStatus(User $user): User
    {
        $user->update([
            'is_active' => !$user->is_active
        ]);

        return $user->fresh();
    }

    /**
     * E-posta benzersizliğini kontrol et
     */
    public function isEmailUnique(string $email, ?int $userId = null, ?int $tenantId = null): bool
    {
        $userModel = config('user-management-module.user_model', User::class);
        $query = $userModel::where('email', $email);

        if ($userId) {
            $query->where('id', '!=', $userId);
        }

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->count() === 0;
    }
}
