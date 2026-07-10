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

    /**
     * Get the full URL for the business logo.
     */
    public function getLogoUrlAttribute($value): ?string
    {
        if (!$value) {
            return null;
        }

        // If it's already a full URL
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        // If it starts with /storage/ or storage/
        if (str_starts_with($value, '/storage/')) {
            return asset(substr($value, 1));
        }

        if (str_starts_with($value, 'storage/')) {
            return asset($value);
        }

        return asset('storage/' . $value);
    }
}
