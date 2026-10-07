<x-app-layout>
    <x-slot name="title">Dashboard | Infinit ERP</x-slot>

    <div class="mx-auto max-w-7xl space-y-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Dashboard</h1>
                <p class="mt-1 text-sm text-slate-500">Visão geral do seu negócio.</p>
            </div>

            <button
                type="button"
                disabled
                class="inline-flex cursor-not-allowed items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-500 shadow-sm"
                title="Disponível quando houver dados de vendas"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3v3m10-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v14H4V6a1 1 0 0 1 1-1Z" />
                </svg>
                Todo o período
            </button>
        </div>

        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Indicadores">
            <x-dashboard.metric-card label="Total de vendas" value="R$ 0,00" icon="cart" />
            <x-dashboard.metric-card label="Nº de vendas" value="0" icon="tag" />
            <x-dashboard.metric-card label="Clientes" value="0" icon="users" />
            <x-dashboard.metric-card label="Produtos vendidos" value="0" icon="box" />
        </section>

        <section class="grid grid-cols-1 gap-4 xl:grid-cols-5" aria-label="Relatórios de vendas">
            <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-3">
                <h2 class="font-semibold text-slate-900">Vendas por dia</h2>

                <div class="mt-5 flex min-h-64 flex-col items-center justify-center rounded-lg border border-dashed border-slate-200 bg-slate-50/70 px-6 text-center">
                    <span class="flex h-11 w-11 items-center justify-center rounded-full bg-white text-violet-500 shadow-sm">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 19V5m0 14h16M7 15l4-4 3 2 5-6" />
                        </svg>
                    </span>
                    <p class="mt-3 text-sm font-medium text-slate-700">Nenhuma venda registrada</p>
                    <p class="mt-1 text-xs text-slate-500">Os dados diários aparecerão aqui.</p>
                </div>
            </article>

            <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-2">
                <h2 class="font-semibold text-slate-900">Vendas por vendedor</h2>

                <div class="mt-5 flex min-h-64 flex-col items-center justify-center rounded-lg border border-dashed border-slate-200 bg-slate-50/70 px-6 text-center">
                    <span class="flex h-11 w-11 items-center justify-center rounded-full bg-white text-violet-500 shadow-sm">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 20V10m7 10V4m7 16v-7" />
                        </svg>
                    </span>
                    <p class="mt-3 text-sm font-medium text-slate-700">Nenhuma venda registrada</p>
                    <p class="mt-1 text-xs text-slate-500">O desempenho dos vendedores aparecerá aqui.</p>
                </div>
            </article>
        </section>
    </div>
</x-app-layout>
