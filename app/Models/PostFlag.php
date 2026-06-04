<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostFlag extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'post_id',
        'user_id',
        'reason',
        'comment',
    ];

    /**
     * Get the post that was flagged.
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Get the user who flagged the post.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
