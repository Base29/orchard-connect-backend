<x-filament-widgets::widget>
    <x-filament::section>
        <div style="display: flex; flex-direction: column; gap: 1rem; width: 100%; align-items: flex-start;">
            <!-- Header section: Status Indicator, Title and Status Badge -->
            <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                <!-- Pulsing Status Indicator -->
                <div style="position: relative; width: 10px; height: 10px; display: inline-flex; align-items: center; justify-content: center;">
                    <div style="position: absolute; width: 10px; height: 10px; border-radius: 50%; animation: pulse-indicator-verif 2s infinite; background-color: {{ $isEnabled ? '#10b981' : '#f59e0b' }};"></div>
                    <div style="position: relative; width: 10px; height: 10px; border-radius: 50%; background-color: {{ $isEnabled ? '#10b981' : '#f59e0b' }};"></div>
                </div>
                
                <span class="text-sm font-bold text-gray-950 dark:text-white" style="letter-spacing: -0.01em;">
                    Residency Verification
                </span>

                <!-- Status Badge -->
                <span class="inline-flex items-center gap-x-1.5 rounded-full px-2.5 py-0.5 text-xs font-bold {{ $isEnabled ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-450 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-600 dark:text-amber-450 border border-amber-500/20' }}" style="font-size: 0.7rem;">
                    {{ $isEnabled ? 'Enabled / Active' : 'Disabled / Bypassed' }}
                </span>
            </div>

            <!-- Description Section -->
            <p class="text-xs text-gray-500 dark:text-gray-400 font-light" style="line-height: 1.5; margin: 0; text-align: left; max-width: 100%;">
                {{ $isEnabled 
                    ? 'Residents are required to upload an official Electricity or Maintenance bill and undergo administrator review to unlock write permissions.' 
                    : 'Residency checks are bypassed. Registered residents only need to verify their email address to access full platform features.' }}
            </p>

            <!-- Action Button at the bottom -->
            <div style="margin-top: 0.25rem; display: flex; align-items: center;">
                <x-filament::button
                    wire:click="toggleVerification"
                    color="{{ $isEnabled ? 'warning' : 'success' }}"
                    icon="{{ $isEnabled ? 'heroicon-m-no-symbol' : 'heroicon-m-check-circle' }}"
                    class="font-bold cursor-pointer"
                    style="border-radius: 9999px !important; padding: 0.5rem 1.25rem !important;"
                >
                    {{ $isEnabled ? 'Disable Verification' : 'Enable Verification' }}
                </x-filament::button>
            </div>
        </div>
    </x-filament::section>

    <!-- Inject custom pulse animation styles -->
    <style>
        @keyframes pulse-indicator-verif {
            0% {
                transform: scale(0.95);
                opacity: 0.85;
                box-shadow: 0 0 0 0 {{ $isEnabled ? 'rgba(16, 185, 129, 0.7)' : 'rgba(245, 158, 11, 0.7)' }};
            }
            70% {
                transform: scale(1.6);
                opacity: 0;
                box-shadow: 0 0 0 6px {{ $isEnabled ? 'rgba(16, 185, 129, 0)' : 'rgba(245, 158, 11, 0)' }};
            }
            100% {
                transform: scale(0.95);
                opacity: 0.85;
                box-shadow: 0 0 0 0 {{ $isEnabled ? 'rgba(16, 185, 129, 0)' : 'rgba(245, 158, 11, 0)' }};
            }
        }
    </style>
</x-filament-widgets::widget>
