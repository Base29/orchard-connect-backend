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
            ->logOnly(['title', 'description', 'start_at', 'end_at', 'status', 'is_anonymous'])
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
        'is_anonymous',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'is_anonymous' => \App\Casts\PostgresSafeBoolean::class,
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

    protected static function booted()
    {
        static::created(function ($poll) {
            if ($poll->status === 'active') {
                $poll->sendNotifications();
            }
        });

        static::updated(function ($poll) {
            if ($poll->wasChanged('status') && $poll->status === 'active') {
                $poll->sendNotifications();
            }
        });
    }

    public function sendNotifications()
    {
        try {
            $residents = User::whereHas('residentProfile', function ($query) {
                $query->where('is_verified', true)->orWhere('status', 'approved');
            })
            ->where('id', '!=', $this->user_id) // don't notify creator
            ->get();

            foreach ($residents as $resident) {
                $resident->notify(new \App\Notifications\GeneralNotification(
                    'New Community Poll',
                    "A new poll has been proposed: \"{$this->title}\"",
                    '/dashboard/polls',
                    ['type' => 'new_poll', 'poll_id' => $this->id]
                ));
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Poll notification dispatch failed: ' . $e->getMessage());
        }
    }
}
