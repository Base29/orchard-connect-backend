<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Post extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'content',
        'media_urls',
        'status',
        'flags_count',
    ];

    protected $casts = [
        'media_urls' => 'array',
        'flags_count' => 'integer',
    ];

    /**
     * Accessor to dynamically sign private S3 URLs.
     */
    public function getMediaUrlsAttribute($value)
    {
        if (!$value) {
            return [];
        }

        $urls = is_array($value) ? $value : json_decode($value, true);
        if (!is_array($urls)) {
            return [];
        }

        try {
            $s3Host = parse_url(\Illuminate\Support\Facades\Storage::disk('s3')->url('s3_test_prefix'), PHP_URL_HOST);
        } catch (\Exception $e) {
            $s3Host = null;
        }

        return array_map(function ($url) use ($s3Host) {
            if ($s3Host) {
                $parsed = parse_url($url);
                $host = $parsed['host'] ?? '';
                if ($host === $s3Host) {
                    $path = ltrim($parsed['path'] ?? '', '/');
                    if ($path) {
                        try {
                            return \Illuminate\Support\Facades\Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(30));
                        } catch (\Exception $e) {
                            return $url;
                        }
                    }
                }
            }
            return $url;
        }, $urls);
    }

    /**
     * Get the author of the post.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all comments associated with this post.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Get only top-level comments (not replies).
     */
    public function topLevelComments(): HasMany
    {
        return $this->hasMany(Comment::class)->whereNull('parent_id');
    }

    /**
     * Get all likes associated with this post (polymorphic relationship).
     */
    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    /**
     * Get all flags associated with this post.
     */
    public function flags(): HasMany
    {
        return $this->hasMany(PostFlag::class);
    }
}
