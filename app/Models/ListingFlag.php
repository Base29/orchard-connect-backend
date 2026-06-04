<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingFlag extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'listing_id',
        'user_id',
        'reason',
        'comment',
    ];

    /**
     * Get the listing that was flagged.
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    /**
     * Get the user who flagged the listing.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
