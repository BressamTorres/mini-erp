<div
    x-cloak
    x-show="sidebarOpen"
    x-transition.opacity
    class="fixed inset-0 z-40 bg-slate-950/40 lg:hidden"
    @click="sidebarOpen = false"
></div>

<aside
    class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col border-r border-slate-200 bg-white transition-transform duration-200 lg:translate-x-0"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
>
    <div class="flex h-16 items-center justify-between border-b border-slate-100 px-6">
        <a href="{{ route('dashboard') }}" class="text-xl font-bold tracking-tight text-slate-900">
            <span class="text-violet-600">Infinit</span> ERP
        </a>

        <button
            type="button"
            class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden"
            @click="sidebarOpen = false"
            aria-label="Fechar menu"
        >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <nav class="flex-1 space-y-1.5 px-3 py-5" aria-label="Navegação principal">
        <a
            href="{{ route('dashboard') }}"
            class="flex items-center gap-3 rounded-lg bg-gradient-to-r from-violet-600 to-violet-700 px-3 py-2.5 text-sm font-medium text-white shadow-sm shadow-violet-200"
            aria-current="page"
            @click="sidebarOpen = false"
        >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 13.2 12 5l9 8.2M5.5 11.4V20h13v-8.6M9 20v-5h6v5" />
            </svg>
            Dashboard
        </a>

        @foreach ([
            ['Clientes', 'M4 19v-1a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v1M10 10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm8 1a3 3 0 0 1 3 3v1M16 3.2a3 3 0 0 1 0 5.6'],
            ['Vendedores', 'M4 19v-1a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v1M10 10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm8 1a3 3 0 0 1 3 3v1M16 3.2a3 3 0 0 1 0 5.6'],
            ['Produtos', 'm4 7 8-4 8 4-8 4-8-4Zm0 0v10l8 4 8-4V7M12 11v10'],
            ['Vendas', 'M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.6L20 8H6M10 20h.01M17 20h.01'],
        ] as [$label, $icon])
            <span
                class="flex cursor-not-allowed items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-500"
                title="Módulo em breve"
                aria-disabled="true"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $icon }}" />
                </svg>
                {{ $label }}
            </span>
        @endforeach
    </nav>

    <div class="border-t border-slate-100 px-6 py-4 text-xs text-slate-400">
        Módulos em preparação
    </div>
</aside>
