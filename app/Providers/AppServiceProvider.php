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

        // Register Spatie Model Policies explicitly
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Permission::class, PermissionPolicy::class);
        Gate::policy(Announcement::class, AnnouncementPolicy::class);
        Gate::policy(\App\Models\ResidentProfile::class, \App\Policies\ResidentProfilePolicy::class);
        Gate::policy(\App\Models\Poll::class, \App\Policies\PollPolicy::class);
    }
}
