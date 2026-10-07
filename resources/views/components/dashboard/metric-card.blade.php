@props(['label', 'value', 'icon'])

<article {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200 bg-white p-5 shadow-sm']) }}>
    <div class="flex items-start justify-between gap-4">
        <div>
            <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
            <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900">{{ $value }}</p>
        </div>

        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-violet-50 text-violet-600">
            @if ($icon === 'cart')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.6L20 8H6M10 20h.01M17 20h.01" />
                </svg>
            @elseif ($icon === 'tag')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 13 13 20l-9-9V4h7l9 9ZM8.5 8.5h.01" />
                </svg>
            @elseif ($icon === 'users')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 19v-1a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v1M10 10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm8 1a3 3 0 0 1 3 3v1M16 3.2a3 3 0 0 1 0 5.6" />
                </svg>
            @else
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m4 7 8-4 8 4-8 4-8-4Zm0 0v10l8 4 8-4V7M12 11v10" />
                </svg>
            @endif
        </span>
    </div>
</article>
