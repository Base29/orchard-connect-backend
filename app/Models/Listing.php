<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Listing extends Model
{
    use HasFactory, HasUuids, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'price', 'status'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

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
     * Accessor to dynamically sign private S3 URLs.
     */
    public function getImagesAttribute($value)
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
     * Mutator to format phone numbers to the +92 Pakistani country code format.
     */
    public function setContactWhatsappAttribute($value)
    {
        if (empty($value)) {
            $this->attributes['contact_whatsapp'] = $value;
            return;
        }

        // Clean whitespaces, dashes, and other non-digit/non-plus characters
        $phone = preg_replace('/[^\d+]/', '', $value);

        if (str_starts_with($phone, '+92')) {
            $this->attributes['contact_whatsapp'] = $phone;
        } elseif (str_starts_with($phone, '0092')) {
            $this->attributes['contact_whatsapp'] = '+92' . substr($phone, 4);
        } elseif (str_starts_with($phone, '92')) {
            $this->attributes['contact_whatsapp'] = '+' . $phone;
        } elseif (str_starts_with($phone, '0')) {
            $this->attributes['contact_whatsapp'] = '+92' . substr($phone, 1);
        } else {
            if (!str_starts_with($phone, '+')) {
                $this->attributes['contact_whatsapp'] = '+92' . $phone;
            } else {
                $this->attributes['contact_whatsapp'] = $phone;
            }
        }
    }

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
