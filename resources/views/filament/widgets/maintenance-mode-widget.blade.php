<x-filament-widgets::widget>
    <x-filament::section>
        <div style="display: flex; flex-direction: column; gap: 1rem; width: 100%; align-items: flex-start;">
            <!-- Header section: Status Indicator, Title and Status Badge -->
            <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                <!-- Pulsing Status Indicator -->
                <div style="position: relative; width: 10px; height: 10px; display: inline-flex; align-items: center; justify-content: center;">
                    <div style="position: absolute; width: 10px; height: 10px; border-radius: 50%; animation: pulse-indicator-data 2s infinite; background-color: {{ $isMaintenance ? '#ef4444' : '#10b981' }};"></div>
                    <div style="position: relative; width: 10px; height: 10px; border-radius: 50%; background-color: {{ $isMaintenance ? '#ef4444' : '#10b981' }};"></div>
                </div>
                
                <span class="text-sm font-bold text-gray-950 dark:text-white" style="letter-spacing: -0.01em;">
                    Platform Status
                </span>

                <!-- Status Badge -->
                <span class="inline-flex items-center gap-x-1.5 rounded-full px-2.5 py-0.5 text-xs font-bold {{ $isMaintenance ? 'bg-rose-500/10 text-rose-600 dark:text-rose-450 border border-rose-500/20' : 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-450 border border-emerald-500/20' }}" style="font-size: 0.7rem;">
                    {{ $isMaintenance ? 'Offline / Maintenance' : 'Online / Live' }}
                </span>
            </div>

            <!-- Description Section -->
            <p class="text-xs text-gray-500 dark:text-gray-400 font-light" style="line-height: 1.5; margin: 0; text-align: left; max-width: 100%;">
                {{ $isMaintenance 
                    ? 'The resident platform is in maintenance mode. Residents see a scheduled maintenance screen. Filament admin dashboard remains open.' 
                    : 'The resident platform is online. All verified community features, marketplace, and social feed are accessible.' }}
            </p>

            <!-- Action Button at the bottom (consistent vertical card flow) -->
            <div style="margin-top: 0.25rem; display: flex; align-items: center;">
                <x-filament::button
                    wire:click="toggleMaintenance"
                    color="{{ $isMaintenance ? 'success' : 'danger' }}"
                    icon="{{ $isMaintenance ? 'heroicon-m-power' : 'heroicon-m-no-symbol' }}"
                    class="font-bold cursor-pointer"
                    style="border-radius: 9999px !important; padding: 0.5rem 1.25rem !important;"
                >
                    {{ $isMaintenance ? 'Resume Service' : 'Trigger Maintenance' }}
                </x-filament::button>
            </div>
        </div>
    </x-filament::section>

    <!-- Inject custom pulse animation styles -->
    <style>
        @keyframes pulse-indicator-data {
            0% {
                transform: scale(0.95);
                opacity: 0.85;
                box-shadow: 0 0 0 0 {{ $isMaintenance ? 'rgba(239, 68, 68, 0.7)' : 'rgba(16, 185, 129, 0.7)' }};
            }
            70% {
                transform: scale(1.6);
                opacity: 0;
                box-shadow: 0 0 0 6px {{ $isMaintenance ? 'rgba(239, 68, 68, 0)' : 'rgba(16, 185, 129, 0)' }};
            }
            100% {
                transform: scale(0.95);
                opacity: 0.85;
                box-shadow: 0 0 0 0 {{ $isMaintenance ? 'rgba(239, 68, 68, 0)' : 'rgba(16, 185, 129, 0)' }};
            }
        }
    </style>
</x-filament-widgets::widget>
