<x-app-layout>
    <x-slot name="title">Dashboard | Infinit ERP</x-slot>

    <div class="mx-auto max-w-7xl space-y-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Dashboard</h1>
                <p class="mt-1 text-sm text-slate-500">Visão geral do seu negócio.</p>
            </div>

            <div x-data="dashboardPeriodFilter(@js([
                'startDate' => $period['startDate'],
                'endDate' => $period['endDate'],
            ]))" class="relative" @click.outside="closePeriod()" @keydown.escape.window="open && closePeriod(true)">
                <form
                    x-ref="periodForm"
                    method="GET"
                    action="{{ route('dashboard') }}"
                    @submit.prevent="applyPeriod"
                >
                    <input type="hidden" name="start_date" :value="startDate">
                    <input type="hidden" name="end_date" :value="endDate">

                    <button
                        x-ref="trigger"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm shadow-sm transition-colors hover:border-violet-200 hover:bg-violet-50/30 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2"
                        aria-haspopup="dialog"
                        :aria-expanded="open"
                        aria-controls="dashboard-period-popover"
                        @click="open ? closePeriod() : openPeriod()"
                    >
                        <svg class="h-4 w-4 shrink-0 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3v3m10-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v14H4V6a1 1 0 0 1 1-1Z" />
                        </svg>

                        <span
                            class="w-[5.75rem] text-center font-medium tabular-nums"
                            :class="startDate ? 'text-slate-700' : 'text-slate-400'"
                            x-text="startDateLabel"
                        >{{ $period['startDate'] ? implode('/', array_reverse(explode('-', $period['startDate']))) : 'DD/MM/AAAA' }}</span>

                        <span class="text-slate-400" aria-hidden="true">→</span>

                        <span
                            class="w-[5.75rem] text-center font-medium tabular-nums"
                            :class="endDate ? 'text-slate-700' : 'text-slate-400'"
                            x-text="endDateLabel"
                        >{{ $period['endDate'] ? implode('/', array_reverse(explode('-', $period['endDate']))) : 'DD/MM/AAAA' }}</span>
                    </button>

                    <div
                        id="dashboard-period-popover"
                        x-cloak
                        x-show="open"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-1"
                        class="absolute right-0 z-50 mt-2 w-[calc(100vw-2rem)] max-w-[21rem] rounded-xl border border-slate-200 bg-white p-4 shadow-xl shadow-slate-200/60"
                        role="dialog"
                        aria-labelledby="dashboard-period-title"
                    >
                        <div class="mb-4">
                            <h2 id="dashboard-period-title" class="text-sm font-semibold text-slate-900">Selecionar período</h2>
                            <p class="mt-1 text-xs text-slate-500">Escolha a data inicial e a data final.</p>
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label for="dashboard-start-date" class="mb-1.5 block text-xs font-medium text-slate-700">Data inicial</label>
                                <input
                                    x-ref="startDate"
                                    id="dashboard-start-date"
                                    type="date"
                                    x-model="draftStartDate"
                                    :max="draftEndDate || null"
                                    @input="validateDraft"
                                    class="block w-full rounded-lg border-slate-300 text-sm text-slate-700 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                >
                            </div>

                            <div>
                                <label for="dashboard-end-date" class="mb-1.5 block text-xs font-medium text-slate-700">Data final</label>
                                <input
                                    id="dashboard-end-date"
                                    type="date"
                                    x-model="draftEndDate"
                                    :min="draftStartDate || null"
                                    @input="validateDraft"
                                    class="block w-full rounded-lg border-slate-300 text-sm text-slate-700 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                >
                            </div>
                        </div>

                        <p x-cloak x-show="validationMessage" x-text="validationMessage" class="mt-2 text-xs text-red-600" role="alert"></p>

                        <div class="mt-4 flex items-center justify-between gap-3 border-t border-slate-100 pt-4">
                            <button type="button" class="text-xs font-medium text-slate-500 transition-colors hover:text-violet-700 focus:outline-none focus:underline" @click="clearDraft">
                                Limpar período
                            </button>

                            <div class="flex items-center gap-2">
                                <button type="button" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 transition-colors hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2" @click="closePeriod(true)">
                                    Cancelar
                                </button>
                                <button
                                    type="button"
                                    class="rounded-lg bg-violet-600 px-3 py-2 text-xs font-semibold text-white transition-colors hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-violet-300"
                                    :disabled="!canApply"
                                    @click="applyPeriod"
                                >
                                    Aplicar
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                @if ($errors->has('start_date') || $errors->has('end_date'))
                    <p class="mt-1 text-right text-xs text-red-600">
                        {{ $errors->first('start_date') ?: $errors->first('end_date') }}
                    </p>
                @endif
            </div>
        </div>

        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Indicadores">
            <x-dashboard.metric-card label="Total de vendas" :value="$indicators['totalSales']" icon="cart" />
            <x-dashboard.metric-card label="Nº de vendas" :value="$indicators['salesCount']" icon="tag" />
            <x-dashboard.metric-card label="Clientes" :value="$indicators['customers']" icon="users" />
            <x-dashboard.metric-card label="Produtos vendidos" :value="$indicators['productsSold']" icon="box" />
        </section>

        <section class="grid grid-cols-1 gap-4 xl:grid-cols-5" aria-label="Relatórios de vendas">
            <article class="h-full rounded-xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-3">
                <h2 class="font-semibold text-slate-900">Vendas por dia</h2>

                @if ($dailySalesChart['items'] !== [])
                    <div
                        x-data="dashboardLineChart(@js($dailySalesChart['items']))"
                        class="mt-5 grid min-h-64 grid-cols-[auto_minmax(0,1fr)] gap-3"
                        aria-label="Faturamento diário"
                    >
                        <div class="flex h-[11rem] flex-col justify-between pb-4 text-right text-[11px] text-slate-400">
                            @foreach ($dailySalesChart['scale'] as $scale)
                                <span>{{ $scale }}</span>
                            @endforeach
                        </div>

                        <div class="min-w-0">
                            <svg
                                class="h-[11rem] w-full overflow-visible"
                                viewBox="0 0 600 176"
                                role="img"
                                aria-label="Gráfico de vendas por dia"
                                @mouseleave="hideTooltip"
                            >
                                <defs>
                                    <linearGradient id="dashboard-sales-area" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#7C3AED" stop-opacity="0.18" />
                                        <stop offset="72%" stop-color="#7C3AED" stop-opacity="0.05" />
                                        <stop offset="100%" stop-color="#7C3AED" stop-opacity="0" />
                                    </linearGradient>
                                </defs>

                                <line x1="0" y1="16" x2="600" y2="16" stroke="#E5E7EB" stroke-width="0.8" />
                                <line x1="0" y1="88" x2="600" y2="88" stroke="#E5E7EB" stroke-width="0.8" />
                                <line x1="0" y1="160" x2="600" y2="160" stroke="#E5E7EB" stroke-width="0.8" />

                                <path :d="areaPath" fill="url(#dashboard-sales-area)" />
                                <path :d="linePath" fill="none" stroke="#7C3AED" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />

                                @foreach ($dailySalesChart['items'] as $item)
                                    <circle
                                        cx="{{ $item['x'] }}"
                                        cy="{{ $item['y'] }}"
                                        :cx="items[{{ $loop->index }}].x"
                                        :cy="items[{{ $loop->index }}].y"
                                        r="14"
                                        fill="transparent"
                                        tabindex="0"
                                        aria-label="{{ $item['fullLabel'] }}: {{ $item['formattedTotal'] }}"
                                        class="cursor-crosshair focus:outline-none"
                                        @mouseenter="showTooltip({{ $loop->index }})"
                                        @focus="showTooltip({{ $loop->index }})"
                                        @blur="hideTooltip"
                                    />
                                    <circle
                                        x-cloak
                                        x-show="activeIndex === {{ $loop->index }}"
                                        cx="{{ $item['x'] }}"
                                        cy="{{ $item['y'] }}"
                                        :cx="items[{{ $loop->index }}].x"
                                        :cy="items[{{ $loop->index }}].y"
                                        r="4"
                                        fill="#7C3AED"
                                        stroke="#ffffff"
                                        stroke-width="2"
                                        class="pointer-events-none"
                                    />
                                @endforeach

                                <g x-cloak x-show="activeItem" :transform="tooltipTransform" class="pointer-events-none">
                                    <rect width="142" height="48" rx="8" fill="#1E293B" fill-opacity="0.96" />
                                    <text x="11" y="19" fill="#CBD5E1" font-size="11" x-text="activeItem?.fullLabel"></text>
                                    <text x="11" y="37" fill="#FFFFFF" font-size="12" font-weight="600" x-text="activeItem?.formattedTotal"></text>
                                </g>
                            </svg>

                            <div @class([
                                'flex text-xs text-slate-400',
                                'justify-center' => count($dailySalesChart['labels']) === 1,
                                'justify-between' => count($dailySalesChart['labels']) > 1,
                            ])>
                                @foreach ($dailySalesChart['labels'] as $label)
                                    <span>{{ $label }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @else
                    <div class="mt-5 flex min-h-64 flex-col items-center justify-center rounded-lg border border-dashed border-slate-200 bg-slate-50/70 px-6 text-center">
                        <span class="flex h-11 w-11 items-center justify-center rounded-full bg-white text-violet-500 shadow-sm">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 19V5m0 14h16M7 15l4-4 3 2 5-6" />
                            </svg>
                        </span>
                        <p class="mt-3 text-sm font-medium text-slate-700">Nenhuma venda no período</p>
                        <p class="mt-1 text-xs text-slate-500">Os dados diários aparecerão aqui.</p>
                    </div>
                @endif
            </article>

            <article class="h-full rounded-xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-2">
                <h2 class="font-semibold text-slate-900">Vendas por vendedor</h2>

                @if ($sellerSalesChart !== [])
                    <div class="mt-5 flex min-h-64 flex-col justify-center gap-5" aria-label="Faturamento por vendedor">
                        @foreach ($sellerSalesChart as $sellerSale)
                            <div class="grid grid-cols-[minmax(0,0.9fr)_minmax(5rem,1.45fr)_auto] items-center gap-3">
                                <span class="truncate text-sm font-medium text-slate-600">{{ $sellerSale['name'] }}</span>
                                <div class="h-3 overflow-hidden rounded-full bg-[#F3F4F6]">
                                    <div class="h-full rounded-full bg-violet-600" style="width: {{ $sellerSale['percentage'] }}%"></div>
                                </div>
                                <span class="whitespace-nowrap text-right text-xs font-semibold text-slate-700">{{ $sellerSale['formattedTotal'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="mt-5 flex min-h-64 flex-col items-center justify-center rounded-lg border border-dashed border-slate-200 bg-slate-50/70 px-6 text-center">
                        <span class="flex h-11 w-11 items-center justify-center rounded-full bg-white text-violet-500 shadow-sm">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 20V10m7 10V4m7 16v-7" />
                            </svg>
                        </span>
                        <p class="mt-3 text-sm font-medium text-slate-700">Nenhuma venda no período</p>
                        <p class="mt-1 text-xs text-slate-500">O desempenho dos vendedores aparecerá aqui.</p>
                    </div>
                @endif
            </article>
        </section>
    </div>
</x-app-layout>
