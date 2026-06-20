<x-filament-panels::page>
    <style>
        .log-filter-row {
            display: flex;
            align-items: center;
            gap: 1rem;
            width: 100%;
            flex-wrap: wrap;
        }
        .log-search-field {
            flex: 1;
            min-width: 260px;
        }
        .log-level-field {
            width: 200px;
            flex-shrink: 0;
        }
        @media (max-width: 640px) {
            .log-level-field {
                width: 100%;
            }
        }
        .log-table-container {
            width: 100%;
            overflow-x: auto;
            border-radius: 0.75rem;
            border: 1px solid rgba(229, 231, 235, 0.8);
            background-color: #ffffff;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        }
        .dark .log-table-container {
            border-color: rgba(55, 65, 81, 0.4);
            background-color: #0c101d;
        }
        .log-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.875rem;
        }
        .log-table th {
            padding: 0.75rem 1.25rem;
            background-color: #f9fafb;
            border-bottom: 1px solid rgba(229, 231, 235, 0.8);
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #4b5563;
        }
        .dark .log-table th {
            background-color: rgba(17, 24, 39, 0.4);
            border-bottom-color: rgba(55, 65, 81, 0.4);
            color: #9ca3af;
        }
        .log-table td {
            padding: 0.75rem 1.25rem;
            border-bottom: 1px solid rgba(229, 231, 235, 0.6);
            color: #1f2937;
            vertical-align: middle;
        }
        .dark .log-table td {
            border-bottom-color: rgba(55, 65, 81, 0.2);
            color: #d1d5db;
        }
        .log-row {
            transition: background-color 0.15s ease-in-out;
            cursor: pointer;
        }
        .log-row:hover {
            background-color: #f3f4f6;
        }
        .dark .log-row:hover {
            background-color: rgba(55, 65, 81, 0.25);
        }
        .badge-level {
            display: inline-flex;
            align-items: center;
            border-radius: 0.375rem;
            padding: 0.25rem 0.625rem;
            font-size: 0.75rem;
            font-weight: 600;
            line-height: 1.2;
            border: 1px solid transparent;
        }
        .badge-level-error {
            background-color: #fee2e2;
            color: #991b1b;
            border-color: #fca5a5;
        }
        .dark .badge-level-error {
            background-color: rgba(220, 38, 38, 0.15);
            color: #f87171;
            border-color: rgba(220, 38, 38, 0.3);
        }
        .badge-level-warning {
            background-color: #fef3c7;
            color: #92400e;
            border-color: #fcd34d;
        }
        .dark .badge-level-warning {
            background-color: rgba(217, 119, 6, 0.15);
            color: #fbbf24;
            border-color: rgba(217, 119, 6, 0.3);
        }
        .badge-level-info {
            background-color: #dbeafe;
            color: #1e40af;
            border-color: #bfdbfe;
        }
        .dark .badge-level-info {
            background-color: rgba(37, 99, 235, 0.15);
            color: #60a5fa;
            border-color: rgba(37, 99, 235, 0.3);
        }
        .badge-level-other {
            background-color: #f3f4f6;
            color: #374151;
            border-color: #e5e7eb;
        }
        .dark .badge-level-other {
            background-color: rgba(156, 163, 175, 0.15);
            color: #9ca3af;
            border-color: rgba(156, 163, 175, 0.3);
        }
        .log-msg-cell {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            max-width: 600px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>

    <div class="flex flex-col gap-6">
        <!-- Filters Header Card -->
        <div class="fi-card rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" style="margin-bottom: 1.75rem;">
            <div class="log-filter-row">
                <!-- Search Input -->
                <div class="log-search-field">
                    <x-filament::input.wrapper prefix-icon="heroicon-m-magnifying-glass">
                        <x-filament::input 
                            type="text" 
                            wire:model.live.debounce.300ms="search" 
                            placeholder="Search messages, stacktraces..." 
                        />
                    </x-filament::input.wrapper>
                </div>

                <!-- Level Select -->
                <div class="log-level-field">
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="level">
                            <option value="">All Levels</option>
                            @foreach ($levels as $l)
                                <option value="{{ $l }}">{{ $l }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
            </div>
        </div>

        <!-- Table Card -->
        <div class="log-table-container">
            <table class="log-table">
                <thead>
                    <tr>
                        <th style="width: 120px;">Level</th>
                        <th style="width: 180px;">Timestamp</th>
                        <th style="width: 80px;">Env</th>
                        <th>Message</th>
                        <th style="width: 80px; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        @php
                            $badgeClass = match($log['level']) {
                                'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY' => 'badge-level-error',
                                'WARNING', 'NOTICE' => 'badge-level-warning',
                                'INFO' => 'badge-level-info',
                                default => 'badge-level-other',
                            };
                        @endphp
                        <tr class="log-row" onclick="window.location='{{ \App\Filament\Pages\LogDetail::getUrl(['index' => $log['index']]) }}'">
                            <td>
                                <span class="badge-level {{ $badgeClass }}">
                                    {{ $log['level'] ?: 'UNKNOWN' }}
                                </span>
                            </td>
                            <td style="font-family: ui-monospace, monospace; font-size: 0.8rem; color: #4b5563;" class="dark:text-gray-400">
                                {{ $log['timestamp'] }}
                            </td>
                            <td>
                                <span style="font-size: 0.75rem; padding: 0.125rem 0.375rem; border-radius: 0.25rem; background-color: #f3f4f6; color: #374151;" class="dark:bg-gray-800 dark:text-gray-300 font-medium">
                                    {{ $log['env'] }}
                                </span>
                            </td>
                            <td>
                                <div class="log-msg-cell">
                                    {{ $log['message'] }}
                                </div>
                            </td>
                            <td style="text-align: right;" onclick="event.stopPropagation()">
                                <x-filament::icon-button
                                    wire:click="deleteLog({{ $log['index'] }})"
                                    wire:confirm="Are you sure you want to delete this log entry?"
                                    icon="heroicon-m-trash"
                                    color="danger"
                                    size="sm"
                                    tooltip="Delete entry"
                                />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 3rem; color: #6b7280;">
                                No log entries found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            
            @if ($logs->hasPages())
                <div style="padding: 1rem 1.25rem; border-top: 1px solid rgba(229, 231, 235, 0.8);" class="dark:border-gray-800">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
