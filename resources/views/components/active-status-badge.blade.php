@props(['active'])

<span @class([
    'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
    'bg-emerald-50 text-emerald-700' => $active,
    'bg-rose-50 text-rose-700' => ! $active,
])>
    {{ $active ? 'Ativo' : 'Inativo' }}
</span>
