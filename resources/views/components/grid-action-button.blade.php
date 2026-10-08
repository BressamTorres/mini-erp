@props([
    'icon',
    'variant',
    'title',
])

@php
    $variantClasses = match ($variant) {
        'edit' => 'text-violet-600 hover:bg-violet-50 hover:text-violet-800 focus:ring-violet-500',
        'deactivate' => 'text-rose-600 hover:bg-rose-50 hover:text-rose-700 focus:ring-rose-500',
        'view' => 'text-slate-500 hover:bg-slate-100 hover:text-slate-700 focus:ring-slate-400',
    };
@endphp

<button
    type="button"
    title="{{ $title }}"
    {{ $attributes->merge(['class' => "inline-flex h-9 w-9 items-center justify-center rounded-lg border-0 bg-transparent {$variantClasses} transition focus:outline-none focus:ring-2 focus:ring-offset-1"]) }}
>
    <svg
        class="h-[18px] w-[18px] shrink-0"
        width="18"
        height="18"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.8"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
    >
        @if ($icon === 'pencil')
            <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z" />
            <path d="m15 5 4 4" />
        @elseif ($icon === 'trash-2')
            <path d="M3 6h18" />
            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" />
            <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
            <path d="M10 11v6" />
            <path d="M14 11v6" />
        @elseif ($icon === 'eye')
            <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0" />
            <circle cx="12" cy="12" r="3" />
        @endif
    </svg>
</button>
