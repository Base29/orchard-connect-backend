<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Listing extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'price',
        'category',
        'images',
        'contact_whatsapp',
        'status',
    ];

    /**
     * Boot the model and register status update observer.
     */
    protected static function booted()
    {
        static::updated(function ($listing) {
            if ($listing->wasChanged('status')) {
                event(new \App\Events\ListingStatusUpdated(
                    $listing->id,
                    $listing->status,
                    $listing->title,
                    $listing->user_id
                ));
            }
        });
    }

    protected $casts = [
        'images' => 'array',
        'price' => 'decimal:2',
    ];

    /**
     * Accessor to dynamically sign private S3 URLs.
     */
    public function getImagesAttribute($value)
    {
        if (!$value) {
            return [];
        }

        $urls = is_array($value) ? $value : json_decode($value, true);
        if (!is_array($urls)) {
            return [];
        }

        return array_map(function ($url) {
            if (str_contains($url, 'amazonaws.com')) {
                $parsed = parse_url($url);
                $path = ltrim($parsed['path'] ?? '', '/');
                if ($path) {
                    try {
                        return \Illuminate\Support\Facades\Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(30));
                    } catch (\Exception $e) {
                        return $url;
                    }
                }
            }
            return $url;
        }, $urls);
    }

    /**
     * Get the associated user who owns this classified listing.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get comments for this listing.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Get flags/reports for this listing.
     */
    public function flags(): HasMany
    {
        return $this->hasMany(ListingFlag::class);
    }
}
