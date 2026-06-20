<x-filament-panels::page>
    @if ($log)
        <style>
            .log-detail-card {
                background-color: #ffffff;
                border-radius: 0.75rem;
                border: 1px solid rgba(229, 231, 235, 0.8);
                box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
                padding: 1.5rem;
            }
            .dark .log-detail-card {
                border-color: rgba(55, 65, 81, 0.4);
                background-color: #0c101d;
            }
            .detail-grid {
                display: grid;
                grid-template-columns: repeat(1, minmax(0, 1fr));
                gap: 1.5rem;
                margin-bottom: 1.5rem;
            }
            @media (min-width: 768px) {
                .detail-grid {
                    grid-template-columns: repeat(3, minmax(0, 1fr));
                }
            }
            .detail-label {
                font-size: 0.75rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                color: #6b7280;
                margin-bottom: 0.25rem;
                display: block;
            }
            .dark .detail-label {
                color: #9ca3af;
            }
            .detail-val {
                font-size: 0.875rem;
                color: #1f2937;
            }
            .dark .detail-val {
                color: #e5e7eb;
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
            .detail-section {
                border-top: 1px solid rgba(229, 231, 235, 0.8);
                padding-top: 1.5rem;
            }
            .dark .detail-section {
                border-top-color: rgba(55, 65, 81, 0.4);
            }
            .detail-box {
                padding: 1rem;
                border-radius: 0.5rem;
                background-color: #f9fafb;
                border: 1px solid rgba(229, 231, 235, 0.8);
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                font-size: 0.875rem;
                line-height: 1.5;
                white-space: pre-wrap;
                word-break: break-all;
            }
            .dark .detail-box {
                background-color: rgba(17, 24, 39, 0.4);
                border-color: rgba(55, 65, 81, 0.4);
                color: #f3f4f6;
            }
            .detail-pre {
                font-size: 0.75rem;
                max-height: 500px;
                overflow-y: auto;
            }
        </style>

        <!-- Details Card -->
        <div class="log-detail-card">
            <div class="detail-grid">
                <div>
                    <span class="detail-label">Level</span>
                    @php
                        $badgeClass = match($log['level']) {
                            'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY' => 'badge-level-error',
                            'WARNING', 'NOTICE' => 'badge-level-warning',
                            'INFO' => 'badge-level-info',
                            default => 'badge-level-other',
                        };
                    @endphp
                    <span class="badge-level {{ $badgeClass }}">
                        {{ $log['level'] ?: 'UNKNOWN' }}
                    </span>
                </div>

                <div>
                    <span class="detail-label">Timestamp</span>
                    <span class="detail-val" style="font-family: ui-monospace, monospace;">
                        {{ $log['timestamp'] }}
                    </span>
                </div>

                <div>
                    <span class="detail-label">Environment</span>
                    <span style="font-size: 0.75rem; padding: 0.125rem 0.375rem; border-radius: 0.25rem; background-color: #f3f4f6; color: #374151;" class="dark:bg-gray-800 dark:text-gray-300">
                        {{ $log['env'] }}
                    </span>
                </div>
            </div>

            <div class="detail-section">
                <span class="detail-label">Message</span>
                <div class="detail-box">
                    {{ $log['message'] }}
                </div>
            </div>

            @if (!empty($log['stack']))
                <div class="detail-section" style="margin-top: 1.5rem;">
                    <span class="detail-label">Stack Trace & Context</span>
                    <pre class="detail-box detail-pre">{{ $log['stack'] }}</pre>
                </div>
            @endif
        </div>
    @else
        <div style="padding: 3rem; text-align: center; color: #6b7280;" class="log-detail-card">
            Log entry data is not available.
        </div>
    @endif
</x-filament-panels::page>
