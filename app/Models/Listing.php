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
