<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'provider_user_id',
        'token',
        'avatar_url',
    ];

    /**
     * Get the owner user of this social account context.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
