@props([
    'active' => null,
    'model' => null,
])

<span
    @if ($model)
        x-text="{{ $model }} ? 'Ativo' : 'Inativo'"
        x-bind:class="{{ $model }} ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'"
        class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
    @else
        @class([
            'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
            'bg-emerald-50 text-emerald-700' => $active,
            'bg-rose-50 text-rose-700' => ! $active,
        ])
    @endif
>
    @unless ($model)
        {{ $active ? 'Ativo' : 'Inativo' }}
    @endunless
</span>
