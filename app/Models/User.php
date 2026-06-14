<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids, HasRoles, LogsActivity;

    /**
     * The relationships that should always be eager loaded.
     *
     * @var array<string>
     */
    protected $with = ['roles'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'status'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar_url',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Check if the user is suspended or banned.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Determine if the user can access the Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isActive() && $this->hasAnyRole(['superadmin', 'community-admin', 'marketplace-moderator', 'content-moderator', 'support-staff']);
    }

    /**
     * Relational Map: Social Accounts (OAuth OAuth connections)
     */
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    /**
     * Relational Map: Resident profile (address verification details)
     */
    public function residentProfile(): HasOne
    {
        return $this->hasOne(ResidentProfile::class);
    }

    /**
     * Relational Map: Resident Polls
     */
    public function polls(): HasMany
    {
        return $this->hasMany(Poll::class);
    }

    /**
     * Relational Map: Poll Votes cast by user
     */
    public function pollVotes(): HasMany
    {
        return $this->hasMany(PollVote::class);
    }

    /**
     * Relational Map: Community Timeline Feed Posts
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Relational Map: Comments
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Relational Map: Reactions/Likes
     */
    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    /**
     * Relational Map: Marketplace Classified Advertisements
     */
    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    /**
     * Relational Map: Flags/reports submitted by user on listings
     */
    public function listingFlags(): HasMany
    {
        return $this->hasMany(ListingFlag::class);
    }

    /**
     * Custom serialization to map admin roles to status for the frontend.
     */
    public function toArray()
    {
        $array = parent::toArray();
        try {
            if ($this->relationLoaded('roles')) {
                $array['roles'] = $this->roles->pluck('name')->toArray();
            } else {
                $array['roles'] = [];
            }
            if ($this->hasAnyRole(['superadmin', 'community-admin', 'marketplace-moderator', 'content-moderator'])) {
                $array['status'] = 'admin';
            }
        } catch (\Throwable $e) {
            // Fallback in case roles tables are not seeded yet or relation fails
        }
        return $array;
    }

    /**
     * Send custom email verification notification.
     */
    public function sendCustomEmailVerificationNotification(): void
    {
        $verificationUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $this->id, 'hash' => sha1($this->email)]
        );

        \Illuminate\Support\Facades\Mail::to($this->email)->send(
            new \App\Mail\VerifyEmailMailable($this, $verificationUrl)
        );
    }
}
