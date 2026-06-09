<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Poll extends Model
{
    use HasFactory, HasUuids, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'description', 'start_at', 'end_at', 'status'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'start_at',
        'end_at',
        'status',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    /**
     * Get the resident who created the poll.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the choices/options for this poll.
     */
    public function options(): HasMany
    {
        return $this->hasMany(PollOption::class);
    }

    /**
     * Get all votes cast for this poll.
     */
    public function votes(): HasMany
    {
        return $this->hasMany(PollVote::class);
    }

    /**
     * Check if the poll is currently active.
     */
    public function isActive(): bool
    {
        $now = now();
        return $this->status === 'active' && $this->start_at <= $now && $this->end_at >= $now;
    }
}
