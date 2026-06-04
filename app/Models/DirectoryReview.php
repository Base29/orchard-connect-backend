<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DirectoryReview extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'directory_listing_id',
        'rating',
        'comment',
    ];

    /**
     * Get the business listing that this review belongs to.
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(DirectoryListing::class, 'directory_listing_id');
    }

    /**
     * Get the resident user who wrote this review.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
