<?php

namespace Modules\RolePermissionModule\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'display_name',
        'description',
        'is_system',
        'level',
        'is_active',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'level' => 'integer',
    ];

    /**
     * Tenant ilişkisi (opsiyonel)
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    /**
     * Permissions ilişkisi
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')
            ->withTimestamps();
    }

    /**
     * Users ilişkisi (user_roles pivot üzerinden)
     */
    public function users(): BelongsToMany
    {
        $userModel = config('role-permission-module.user_model', 'App\Models\User');

        return $this->belongsToMany($userModel, 'user_roles', 'role_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * Belirli bir permission'a sahip mi?
     */
    public function hasPermission(string $permissionSlug): bool
    {
        return $this->permissions()->where('slug', $permissionSlug)->exists();
    }

    /**
     * Permission ekle
     */
    public function assignPermission(string $permissionSlug): void
    {
        $permission = Permission::where('slug', $permissionSlug)->first();
        if ($permission && !$this->hasPermission($permissionSlug)) {
            $this->permissions()->attach($permission->id);
        }
    }

    /**
     * Permission kaldır
     */
    public function revokePermission(string $permissionSlug): void
    {
        $permission = Permission::where('slug', $permissionSlug)->first();
        if ($permission) {
            $this->permissions()->detach($permission->id);
        }
    }
}
