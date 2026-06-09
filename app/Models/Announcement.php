<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Support\Facades\Storage;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Announcement extends Model
{
    use HasFactory, HasUuids, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'content', 'status'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'content',
        'author_id',
        'category',
        'status',
        'pinned',
        'image_path',
    ];

    protected $appends = [
        'image_url',
    ];

    /**
     * Get the full URL for the announcement image.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image_path) {
            return null;
        }

        // If it's already a full URL
        if (filter_var($this->image_path, FILTER_VALIDATE_URL)) {
            return $this->image_path;
        }

        $keyId = config('filesystems.disks.s3.key');
        $bucket = config('filesystems.disks.s3.bucket');

        if (empty($keyId) || empty($bucket)) {
            return asset('storage/' . $this->image_path);
        }

        return Storage::disk('s3')->url($this->image_path);
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'pinned' => 'boolean',
    ];

    /**
     * Get the moderator user who published this announcement.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
