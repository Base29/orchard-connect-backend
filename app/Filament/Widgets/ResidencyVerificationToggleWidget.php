<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\Setting;
use Filament\Notifications\Notification;

class ResidencyVerificationToggleWidget extends Widget
{
    protected string $view = 'filament.widgets.residency-verification-toggle-widget';

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('superadmin') ?? false;
    }

    protected static ?int $sort = -9;

    protected int|string|array $columnSpan = 1;

    public bool $isEnabled = true;

    public function mount(): void
    {
        $this->isEnabled = (bool) Setting::getValue('residency_verification_enabled', true);
    }

    public function toggleVerification(): void
    {
        $this->isEnabled = !$this->isEnabled;
        Setting::setValue('residency_verification_enabled', $this->isEnabled);

        try {
            Notification::make()
                ->title($this->isEnabled ? 'Residency Verification Activated' : 'Residency Verification Deactivated')
                ->body($this->isEnabled 
                    ? 'Newly registering users are now required to upload proof of residency and obtain administrator approval.' 
                    : 'Newly registering users will bypass residency document upload and approval. They only need to verify their email.')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            logger()->error('Sending Filament notification failed: ' . $e->getMessage());
        }
    }
}
