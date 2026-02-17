<?php

namespace Modules\AuthModule\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserService
{
    protected function userModel(): string
    {
        return config('auth-module.multi_tenant.user_model', User::class);
    }

    public function create(array $data): User
    {
        DB::beginTransaction();
        try {
            $model = $this->userModel();
            $role = $data['role'] ?? config('role-permission-module.default_role_slug', 'user');
            $tenantId = $data['tenant_id'] ?? null;

            $user = $model::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'is_active' => $data['is_active'] ?? true,
                'tenant_id' => $tenantId,
            ]);

            if (class_exists(\Modules\RolePermissionModule\Services\RolePermissionService::class)
                && config('role-permission-module.enabled', true)) {
                app(\Modules\RolePermissionModule\Services\RolePermissionService::class)
                    ->assignRole($user, $role);
            }

            DB::commit();
            return $user;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AuthModule UserService::create', ['error' => $e->getMessage(), 'data' => $data]);
            throw $e;
        }
    }

    public function update(User $user, array $data): User
    {
        DB::beginTransaction();
        try {
            $role = $data['role'] ?? config('role-permission-module.default_role_slug', 'user');

            $updateData = [
                'name' => $data['name'],
                'email' => $data['email'],
                'is_active' => $data['is_active'] ?? $user->is_active,
            ];
            if (! empty($data['password'])) {
                $updateData['password'] = Hash::make($data['password']);
            }
            $user->update($updateData);

            if (isset($data['role'])
                && class_exists(\Modules\RolePermissionModule\Services\RolePermissionService::class)
                && config('role-permission-module.enabled', true)) {
                app(\Modules\RolePermissionModule\Services\RolePermissionService::class)
                    ->syncRoles($user, [$role]);
            }

            DB::commit();
            return $user->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AuthModule UserService::update', ['error' => $e->getMessage(), 'user_id' => $user->id, 'data' => $data]);
            throw $e;
        }
    }

    public function delete(User $user): bool
    {
        DB::beginTransaction();
        try {
            $user->delete();
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AuthModule UserService::delete', ['error' => $e->getMessage(), 'user_id' => $user->id]);
            throw $e;
        }
    }

    public function toggleStatus(User $user): User
    {
        $user->update(['is_active' => ! $user->is_active]);
        return $user->fresh();
    }
}
