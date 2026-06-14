<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class SupportTicket extends Model
{
    use HasFactory, HasUuids, LogsActivity;

    protected $fillable = [
        'tracking_id',
        'user_id',
        'guest_name',
        'guest_email',
        'category',
        'subject',
        'description',
        'status',
        'resolution_notes',
        'assigned_to',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['tracking_id', 'status', 'assigned_to'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * Get the resident who created the support ticket (if authenticated).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the administrative user assigned to this ticket.
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
