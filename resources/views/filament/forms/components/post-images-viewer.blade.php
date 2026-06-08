<div>
    @php
        $mediaUrls = $getState();
    @endphp

    @if ($mediaUrls && is_array($mediaUrls) && count($mediaUrls) > 0)
        <div class="border border-neutral-200 dark:border-zinc-800 rounded-xl overflow-hidden bg-neutral-50 dark:bg-zinc-950 p-4 shadow-sm">
            <div class="mb-3 text-xs text-slate-400 dark:text-zinc-500 font-medium">
                <span>{{ count($mediaUrls) }} {{ count($mediaUrls) === 1 ? 'Image' : 'Images' }} attached</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                @foreach ($mediaUrls as $url)
                    <div class="relative group bg-neutral-100 dark:bg-zinc-900 rounded-lg overflow-hidden border border-neutral-200/40 dark:border-zinc-800/40 aspect-video flex items-center justify-center">
                        <img src="{{ $url }}" alt="Post Image" class="max-w-full h-full object-cover rounded shadow-sm" />
                        <a href="{{ $url }}" target="_blank" class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-xs font-semibold transition-opacity duration-200 gap-1">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-4 h-4" width="16" height="16">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                            View Original
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="p-8 border-2 border-dashed border-neutral-200 dark:border-zinc-800 rounded-xl text-center text-sm text-slate-400 dark:text-zinc-500 font-light">
            No images attached to this post.
        </div>
    @endif
</div>
