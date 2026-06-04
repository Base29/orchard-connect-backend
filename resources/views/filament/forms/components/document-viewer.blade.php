<div>
    @php
        $state = $getState();
        $isPdf = false;
        $url = null;

        if ($state) {
            $url = route('admin.document.view', ['path' => $state]);
            $isPdf = str_ends_with(strtolower($state), '.pdf');
        }
    @endphp

    @if ($state === 'purged')
        <div class="p-8 border border-neutral-200 dark:border-zinc-800 rounded-xl bg-neutral-50 dark:bg-zinc-950/40 text-center space-y-3 shadow-sm">
            <div class="text-4xl text-emerald-500">🛡️</div>
            <div class="space-y-1">
                <h4 class="text-sm font-bold text-slate-800 dark:text-zinc-200">Verification Document Purged</h4>
                <p class="text-xs text-slate-400 dark:text-zinc-500 max-w-sm mx-auto leading-relaxed">
                    This resident has been verified and approved. Verification documents containing personally identifiable information (PII) are automatically deleted from storage after approval for security and privacy compliance.
                </p>
            </div>
        </div>
    @elseif ($state)
        <div class="border border-neutral-200 dark:border-zinc-800 rounded-xl overflow-hidden bg-neutral-50 dark:bg-zinc-950 p-3 shadow-sm">
            <div class="mb-3 flex items-center justify-between text-xs text-slate-400 dark:text-zinc-500 font-medium">
                <span class="font-mono truncate max-w-[200px] sm:max-w-xs">{{ basename($state) }}</span>
                <a href="{{ $url }}" target="_blank" class="text-emerald-500 hover:text-emerald-600 font-semibold flex items-center gap-1.5 transition-colors" style="display: inline-flex; align-items: center; gap: 0.375rem; white-space: nowrap;">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 14px; height: 14px; flex-shrink: 0;" width="14" height="14">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                    Open in New Tab
                </a>
            </div>

            @if ($isPdf)
                <iframe src="{{ $url }}" class="w-full h-[550px] rounded-lg border border-neutral-200/50 dark:border-zinc-800/50" style="min-height: 550px; background-color: #323639;"></iframe>
            @else
                <div class="flex justify-center bg-neutral-100 dark:bg-zinc-900 rounded-lg overflow-hidden p-4 border border-neutral-200/40 dark:border-zinc-800/40">
                    <img src="{{ $url }}" alt="Proof of Residency" class="max-w-full h-auto max-h-[520px] object-contain rounded shadow-sm" />
                </div>
            @endif
        </div>
    @else
        <div class="p-8 border-2 border-dashed border-neutral-200 dark:border-zinc-800 rounded-xl text-center text-sm text-slate-400 dark:text-zinc-500 font-light">
            No verification document uploaded.
        </div>
    @endif
</div>
