<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class ResidentProfile extends Model
{
    use HasFactory, HasUuids, LogsActivity;

    protected static function booted()
    {
        static::updating(function ($profile) {
            if ($profile->isDirty('status')) {
                $newStatus = $profile->status;
                if (($newStatus === 'approved' || $newStatus === 'rejected') && $profile->document_path && $profile->document_path !== 'purged') {
                    try {
                        $storage = app(\App\Services\S3PrivateStorageService::class);
                        $storage->delete($profile->document_path);
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::error("Failed to delete document for profile {$profile->id} on status update: " . $e->getMessage());
                    }
                    $profile->document_path = 'purged';
                }
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'phase',
                'block',
                'house_number',
                'street_number',
                'user_type',
                'status',
                'rejection_reason',
                'is_verified',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected $fillable = [
        'user_id',
        'phase',
        'block',
        'house_number',
        'street_number',
        'user_type',
        'document_path',
        'status',
        'rejection_reason',
        'rejection_message',
        'is_verified',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'is_verified' => \App\Casts\PostgresSafeBoolean::class,
        'verified_at' => 'datetime',
    ];

    /**
     * Get the associated user account.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the staff member who verified this resident.
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
