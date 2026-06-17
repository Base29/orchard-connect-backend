<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\Setting;
use App\Events\MaintenanceModeChanged;
use Filament\Notifications\Notification;

class MaintenanceModeWidget extends Widget
{
    protected string $view = 'filament.widgets.maintenance-mode-widget';

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('superadmin') ?? false;
    }

    protected static ?int $sort = -10;

    protected int|string|array $columnSpan = 1;

    public bool $isMaintenance = false;

    public function mount(): void
    {
        $this->isMaintenance = (bool) Setting::getValue('maintenance_mode', false);
    }

    public function toggleMaintenance(): void
    {
        $this->isMaintenance = !$this->isMaintenance;
        Setting::setValue('maintenance_mode', $this->isMaintenance);

        // Broadcast the real-time status change to all online user sessions (via Reverb)
        try {
            event(new MaintenanceModeChanged($this->isMaintenance));
            logger()->info('Dispatched MaintenanceModeChanged event. Status: ' . ($this->isMaintenance ? 'ON' : 'OFF'));
        } catch (\Throwable $e) {
            logger()->error('Broadcasting MaintenanceModeChanged failed: ' . $e->getMessage());
        }

        try {
            Notification::make()
                ->title($this->isMaintenance ? 'Maintenance Mode Active' : 'Platform Online')
                ->body($this->isMaintenance 
                    ? 'Orchard Connect has been put into maintenance mode. Normal users will see the maintenance screen.' 
                    : 'Maintenance mode has been disabled. The platform is now fully accessible.')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            logger()->error('Sending Filament notification failed: ' . $e->getMessage());
        }
    }
}
