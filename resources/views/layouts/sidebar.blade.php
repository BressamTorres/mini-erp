<div
    x-cloak
    x-show="sidebarOpen"
    x-transition.opacity
    class="fixed inset-0 z-40 bg-slate-950/40 lg:hidden"
    @click="sidebarOpen = false"
></div>

<aside
    class="erp-sidebar fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col border-r border-slate-200 bg-white transition-[width,transform] duration-200 ease-out lg:translate-x-0"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
>
    <div
        class="erp-sidebar-header flex h-16 items-center justify-between border-b border-slate-100 px-6 transition-[padding] duration-200"
    >
        <a
            href="{{ route('dashboard') }}"
            class="erp-sidebar-brand-expanded flex items-center gap-2 overflow-hidden whitespace-nowrap text-xl font-bold tracking-tight text-slate-900"
        >
            <img
                src="{{ asset('images/brand/infinit-symbol-purple.png') }}"
                alt=""
                class="h-auto w-8 shrink-0 object-contain"
                width="32"
                height="18"
            >
            <span
                x-show="! sidebarCollapsed"
                x-transition:enter="transition-opacity ease-out delay-150 duration-75"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                class="shrink-0 whitespace-nowrap"
            ><span class="text-violet-600">Infinit</span> ERP</span>
        </a>

        <a
            href="{{ route('dashboard') }}"
            class="erp-sidebar-brand-collapsed hidden h-10 w-12 items-center justify-center"
            aria-label="Infinit ERP"
        >
            <img
                src="{{ asset('images/brand/infinit-symbol-purple.png') }}"
                alt=""
                class="max-h-8 w-11 object-contain"
                width="44"
                height="25"
            >
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

    <button
        type="button"
        class="absolute -right-3 top-16 hidden h-7 w-7 -translate-y-1/2 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:border-violet-300 hover:text-violet-700 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-1 lg:inline-flex"
        @click="toggleSidebar"
        :aria-label="sidebarCollapsed ? 'Expandir menu lateral' : 'Recolher menu lateral'"
    >
        <svg x-show="! sidebarCollapsed" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 18-6-6 6-6" />
        </svg>
        <svg x-cloak x-show="sidebarCollapsed" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 18 6-6-6-6" />
        </svg>
    </button>

    <nav
        class="erp-sidebar-nav flex-1 space-y-1.5 px-3 py-5 transition-[padding] duration-200"
        aria-label="Navegação principal"
    >
        <a
            href="{{ route('dashboard') }}"
            @class([
                'erp-sidebar-item group relative flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                'bg-gradient-to-r from-violet-600 to-violet-700 text-white shadow-sm shadow-violet-200' => request()->routeIs('dashboard'),
                'text-slate-600 hover:bg-slate-50 hover:text-violet-700' => ! request()->routeIs('dashboard'),
            ])
            aria-label="Dashboard"
            @if (request()->routeIs('dashboard')) aria-current="page" @endif
            @click="sidebarOpen = false"
        >
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 13.2 12 5l9 8.2M5.5 11.4V20h13v-8.6M9 20v-5h6v5" />
            </svg>
            <span class="erp-sidebar-label">Dashboard</span>
            <span x-cloak x-show="sidebarCollapsed" aria-hidden="true" class="invisible absolute left-full top-1/2 z-[60] ml-3 -translate-y-1/2 whitespace-nowrap rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 opacity-0 shadow-lg shadow-slate-200/70 transition duration-150 group-hover:visible group-hover:opacity-100 group-focus-visible:visible group-focus-visible:opacity-100">Dashboard</span>
        </a>

        <a
            href="{{ route('customers.index') }}"
            @class([
                'erp-sidebar-item group relative flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                'bg-gradient-to-r from-violet-600 to-violet-700 text-white shadow-sm shadow-violet-200' => request()->routeIs('customers.*'),
                'text-slate-600 hover:bg-slate-50 hover:text-violet-700' => ! request()->routeIs('customers.*'),
            ])
            aria-label="Clientes"
            @if (request()->routeIs('customers.*')) aria-current="page" @endif
            @click="sidebarOpen = false"
        >
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 19v-1a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v1M10 10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm8 1a3 3 0 0 1 3 3v1M16 3.2a3 3 0 0 1 0 5.6" />
            </svg>
            <span class="erp-sidebar-label">Clientes</span>
            <span x-cloak x-show="sidebarCollapsed" aria-hidden="true" class="invisible absolute left-full top-1/2 z-[60] ml-3 -translate-y-1/2 whitespace-nowrap rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 opacity-0 shadow-lg shadow-slate-200/70 transition duration-150 group-hover:visible group-hover:opacity-100 group-focus-visible:visible group-focus-visible:opacity-100">Clientes</span>
        </a>

        <a
            href="{{ route('sellers.index') }}"
            @class([
                'erp-sidebar-item group relative flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                'bg-gradient-to-r from-violet-600 to-violet-700 text-white shadow-sm shadow-violet-200' => request()->routeIs('sellers.*'),
                'text-slate-600 hover:bg-slate-50 hover:text-violet-700' => ! request()->routeIs('sellers.*'),
            ])
            aria-label="Vendedores"
            @if (request()->routeIs('sellers.*')) aria-current="page" @endif
            @click="sidebarOpen = false"
        >
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 19v-1a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v1M10 10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm8 1a3 3 0 0 1 3 3v1M16 3.2a3 3 0 0 1 0 5.6" />
            </svg>
            <span class="erp-sidebar-label">Vendedores</span>
            <span x-cloak x-show="sidebarCollapsed" aria-hidden="true" class="invisible absolute left-full top-1/2 z-[60] ml-3 -translate-y-1/2 whitespace-nowrap rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 opacity-0 shadow-lg shadow-slate-200/70 transition duration-150 group-hover:visible group-hover:opacity-100 group-focus-visible:visible group-focus-visible:opacity-100">Vendedores</span>
        </a>

        <a
            href="{{ route('products.index') }}"
            @class([
                'erp-sidebar-item group relative flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                'bg-gradient-to-r from-violet-600 to-violet-700 text-white shadow-sm shadow-violet-200' => request()->routeIs('products.*'),
                'text-slate-600 hover:bg-slate-50 hover:text-violet-700' => ! request()->routeIs('products.*'),
            ])
            aria-label="Produtos"
            @if (request()->routeIs('products.*')) aria-current="page" @endif
            @click="sidebarOpen = false"
        >
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m4 7 8-4 8 4-8 4-8-4Zm0 0v10l8 4 8-4V7M12 11v10" />
            </svg>
            <span class="erp-sidebar-label">Produtos</span>
            <span x-cloak x-show="sidebarCollapsed" aria-hidden="true" class="invisible absolute left-full top-1/2 z-[60] ml-3 -translate-y-1/2 whitespace-nowrap rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 opacity-0 shadow-lg shadow-slate-200/70 transition duration-150 group-hover:visible group-hover:opacity-100 group-focus-visible:visible group-focus-visible:opacity-100">Produtos</span>
        </a>

        <a
            href="{{ route('sales.index') }}"
            @class([
                'erp-sidebar-item group relative flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                'bg-gradient-to-r from-violet-600 to-violet-700 text-white shadow-sm shadow-violet-200' => request()->routeIs('sales.*'),
                'text-slate-600 hover:bg-slate-50 hover:text-violet-700' => ! request()->routeIs('sales.*'),
            ])
            aria-label="Vendas"
            @if (request()->routeIs('sales.*')) aria-current="page" @endif
            @click="sidebarOpen = false"
        >
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.6L20 8H6M10 20h.01M17 20h.01" />
            </svg>
            <span class="erp-sidebar-label">Vendas</span>
            <span x-cloak x-show="sidebarCollapsed" aria-hidden="true" class="invisible absolute left-full top-1/2 z-[60] ml-3 -translate-y-1/2 whitespace-nowrap rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 opacity-0 shadow-lg shadow-slate-200/70 transition duration-150 group-hover:visible group-hover:opacity-100 group-focus-visible:visible group-focus-visible:opacity-100">Vendas</span>
        </a>

        <div x-data="{ settingsOpen: @js(request()->routeIs('settings.*')) }" class="space-y-1">
            <button
                type="button"
                @click="if (sidebarCollapsed) { toggleSidebar(); settingsOpen = true } else { settingsOpen = ! settingsOpen }"
                @class([
                    'erp-sidebar-item group relative flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-medium transition',
                    'bg-gradient-to-r from-violet-600 to-violet-700 text-white shadow-sm shadow-violet-200' => request()->routeIs('settings.*'),
                    'text-slate-600 hover:bg-slate-50 hover:text-violet-700' => ! request()->routeIs('settings.*'),
                ])
                aria-label="Configurações"
                :aria-expanded="settingsOpen"
                aria-controls="settings-navigation"
            >
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7ZM19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2.8 2.8-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6v.2h-4V21a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1L4.2 17l.1-.1a1.7 1.7 0 0 0 .3-1.9A1.7 1.7 0 0 0 3 14H2.8v-4H3a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9L4.2 7 7 4.2l.1.1A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-1.6v-.2h4V3a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1L19.8 7l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.6 1h.2v4H21a1.7 1.7 0 0 0-1.6 1Z" />
                </svg>
                <span class="erp-sidebar-label flex-1">Configurações</span>
                <svg x-show="! sidebarCollapsed" class="erp-sidebar-label h-4 w-4 shrink-0 transition-transform" :class="settingsOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6" />
                </svg>
                <span x-cloak x-show="sidebarCollapsed" aria-hidden="true" class="invisible absolute left-full top-1/2 z-[60] ml-3 -translate-y-1/2 whitespace-nowrap rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 opacity-0 shadow-lg shadow-slate-200/70 transition duration-150 group-hover:visible group-hover:opacity-100 group-focus-visible:visible group-focus-visible:opacity-100">Configurações</span>
            </button>

            <div
                id="settings-navigation"
                x-cloak
                x-show="settingsOpen && ! sidebarCollapsed"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="-translate-y-1 opacity-0"
                x-transition:enter-end="translate-y-0 opacity-100"
                class="space-y-1 pl-11 pr-2"
            >
                <a
                    href="{{ route('settings.business.edit') }}"
                    @class([
                        'block rounded-lg px-3 py-2 text-sm transition',
                        'bg-violet-50 font-semibold text-violet-700' => request()->routeIs('settings.business.*'),
                        'text-slate-500 hover:bg-slate-50 hover:text-violet-700' => ! request()->routeIs('settings.business.*'),
                    ])
                    @if (request()->routeIs('settings.business.*')) aria-current="page" @endif
                    @click="sidebarOpen = false"
                >Dados da Empresa</a>
                <a
                    href="{{ route('settings.catalog.show') }}"
                    @class([
                        'block rounded-lg px-3 py-2 text-sm transition',
                        'bg-violet-50 font-semibold text-violet-700' => request()->routeIs('settings.catalog.*'),
                        'text-slate-500 hover:bg-slate-50 hover:text-violet-700' => ! request()->routeIs('settings.catalog.*'),
                    ])
                    @if (request()->routeIs('settings.catalog.*')) aria-current="page" @endif
                    @click="sidebarOpen = false"
                >Meu Catálogo Online</a>
            </div>
        </div>
    </nav>

</aside>
