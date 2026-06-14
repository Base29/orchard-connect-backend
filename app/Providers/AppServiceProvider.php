<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Policies\RolePolicy;
use App\Policies\PermissionPolicy;
use App\Models\Announcement;
use App\Policies\AnnouncementPolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
     }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS in production
        if (config('app.env') === 'production') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // Super Admin bypass for Gate checks
        Gate::before(function ($user, $ability) {
            return $user->hasRole('superadmin') ? true : null;
        });

        // Register Spatie and Application Model Policies explicitly
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Permission::class, PermissionPolicy::class);
        Gate::policy(Announcement::class, AnnouncementPolicy::class);
        Gate::policy(\App\Models\ResidentProfile::class, \App\Policies\ResidentProfilePolicy::class);
        Gate::policy(\App\Models\Poll::class, \App\Policies\PollPolicy::class);
        Gate::policy(\App\Models\User::class, \App\Policies\UserPolicy::class);
        Gate::policy(\App\Models\News::class, \App\Policies\NewsPolicy::class);
        Gate::policy(\App\Models\Post::class, \App\Policies\PostPolicy::class);
        Gate::policy(\App\Models\Comment::class, \App\Policies\CommentPolicy::class);
        Gate::policy(\App\Models\Listing::class, \App\Policies\ListingPolicy::class);
        Gate::policy(\App\Models\DirectoryListing::class, \App\Policies\DirectoryListingPolicy::class);
        Gate::policy(\App\Models\DirectoryCategory::class, \App\Policies\DirectoryCategoryPolicy::class);
        Gate::policy(\App\Models\ModerationLog::class, \App\Policies\ModerationLogPolicy::class);
        Gate::policy(\App\Models\SupportTicket::class, \App\Policies\SupportTicketPolicy::class);

        // Register Notifications Event Listener
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\CommentCreated::class,
            [\App\Listeners\SendPlatformNotification::class, 'handle']
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\PostLiked::class,
            [\App\Listeners\SendPlatformNotification::class, 'handle']
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\ResidentVerificationStatusUpdated::class,
            [\App\Listeners\SendPlatformNotification::class, 'handle']
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\ListingStatusUpdated::class,
            [\App\Listeners\SendPlatformNotification::class, 'handle']
        );
    }
}
