<?php

namespace Modules\AuthModule\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialAccount extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'provider_id',
        'email',
        'name',
        'avatar',
        'provider_data',
    ];

    protected $casts = [
        'provider_data' => 'array',
    ];

    /**
     * User ilişkisi
     */
    public function user(): BelongsTo
    {
        $userModel = config('auth-module.multi_tenant.user_model', 'App\Models\User');
        return $this->belongsTo($userModel);
    }
}
