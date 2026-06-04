<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Post;
use App\Models\Listing;
use App\Models\ResidentProfile;
use App\Models\PostFlag;
use App\Models\ListingFlag;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        // 1. Resident Verification Stats
        $verifiedCount = ResidentProfile::whereRaw('is_verified = true')->count();
        $pendingCount = ResidentProfile::where('status', 'pending')->count();
        
        $sevenDaysAgo = Carbon::now()->subDays(6)->startOfDay();

        // 2. Timeline Activity Stats (Post counts grouped by day for last 7 days)
        $postsCount = Post::count();
        $postsByDay = Post::where('created_at', '>=', $sevenDaysAgo)
            ->selectRaw('DATE(created_at) as date, count(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date');

        $postTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->toDateString();
            $postTrend[] = $postsByDay->get($date, 0);
        }

        // 3. Marketplace Activity (Listing counts grouped by day for last 7 days)
        $listingsCount = Listing::count();
        $listingsByDay = Listing::where('created_at', '>=', $sevenDaysAgo)
            ->selectRaw('DATE(created_at) as date, count(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date');

        $listingTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->toDateString();
            $listingTrend[] = $listingsByDay->get($date, 0);
        }

        // 4. Moderation Queue Flags
        $flagsCount = PostFlag::count() + ListingFlag::count();

        return [
            Stat::make('Verified Residents', $verifiedCount)
                ->description($pendingCount > 0 ? "{$pendingCount} profiles awaiting review" : 'All profiles verified')
                ->descriptionIcon($pendingCount > 0 ? 'heroicon-m-clock' : 'heroicon-m-check-circle')
                ->color($pendingCount > 0 ? 'warning' : 'success'),

            Stat::make('Timeline Discussions', $postsCount)
                ->chart($postTrend)
                ->description('Community social activity')
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->color('success'),

            Stat::make('Marketplace Ads', $listingsCount)
                ->chart($listingTrend)
                ->description('Active local classifieds')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('info'),

            Stat::make('Flagged Content', $flagsCount)
                ->description('Reports needing moderation')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($flagsCount > 0 ? 'danger' : 'success'),
        ];
    }
}
