<?php

namespace Modules\RbacModule\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;
    protected $dates = ['created_at'];

    protected $fillable = [
        'tenant_id',
        'user_id',
        'action',
        'entity',
        'entity_id',
        'ip_address',
        'user_agent',
        'method',
        'url',
        'old_values',
        'new_values',
        'meta',
        'execution_time_ms',
        'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'meta' => 'array',
        'execution_time_ms' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Tenant ilişkisi
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    /**
     * User ilişkisi
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
