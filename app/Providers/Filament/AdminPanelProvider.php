<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('Orchard Connect')
            ->brandLogo(fn () => view('filament.admin.logo'))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('favicon.ico'))
            ->font('Plus Jakarta Sans')
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()
            ->colors([
                'danger' => Color::Rose,
                'gray' => Color::Zinc,
                'info' => Color::Blue,
                'primary' => Color::Emerald,
                'success' => Color::Teal,
                'warning' => Color::Amber,
            ])
            ->renderHook(
                \Filament\View\PanelsRenderHook::HEAD_END,
                fn (): \Illuminate\Contracts\View\View => view('filament.admin.styles'),
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                \App\Filament\Widgets\MaintenanceModeWidget::class,
                \App\Filament\Widgets\ResidencyVerificationToggleWidget::class,
                \App\Filament\Widgets\StatsOverview::class,
                \App\Filament\Widgets\UserGrowthChart::class,
                \App\Filament\Widgets\PendingVerifications::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
