<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PollOption extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'poll_id',
        'option_text',
    ];

    /**
     * Get the parent poll.
     */
    public function poll(): BelongsTo
    {
        return $this->belongsTo(Poll::class);
    }

    /**
     * Get all votes cast for this specific option.
     */
    public function votes(): HasMany
    {
        return $this->hasMany(PollVote::class);
    }
}
