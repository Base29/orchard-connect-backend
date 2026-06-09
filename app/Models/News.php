<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Support\Facades\Storage;

class News extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'title',
        'content',
        'author_id',
        'status',
        'image_path',
    ];

    protected $appends = [
        'image_url',
    ];

    /**
     * Get the full URL for the featured image.
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
     * Get the author (user) who wrote this news article.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Get all comments associated with this news article.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
