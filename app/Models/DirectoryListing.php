<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DirectoryListing extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'address',
        'contact_phone',
        'whatsapp',
        'logo_url',
        'is_verified',
    ];

    protected $casts = [
        'is_verified' => \App\Casts\PostgresSafeBoolean::class,
    ];

    /**
     * Get the category classification of this directory listing.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(DirectoryCategory::class, 'category_id');
    }

    /**
     * Get all reviews and ratings submitted for this business listing.
     */
    public function reviews(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DirectoryReview::class, 'directory_listing_id');
    }
}
