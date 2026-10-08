@php
    $oldSaleId = (string) old('sale_id', '');
    $isEditing = old('form_mode') === 'edit' && ctype_digit($oldSaleId);
    $saleFormConfig = [
        'customers' => $customers,
        'sellers' => $sellers,
        'products' => $products,
        'storeUrl' => route('sales.store'),
        'form' => [
            'open' => $errors->any(),
            'mode' => $isEditing ? 'edit' : 'create',
            'action' => $isEditing ? route('sales.update', ['sale' => $oldSaleId]) : route('sales.store'),
            'saleId' => $oldSaleId,
            'fields' => [
                'customer_id' => (string) old('customer_id', ''),
                'seller_id' => (string) old('seller_id', ''),
                'active' => (string) old('active', '1'),
            ],
            'items' => old('items', []),
            'historicalSale' => $formSale,
        ],
    ];
@endphp

<x-app-layout>
    <x-slot name="title">Vendas | Infinit ERP</x-slot>

    <div x-data="salesPage({{ Js::from($saleFormConfig) }})" class="mx-auto max-w-7xl space-y-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Vendas</h1>
                <p class="mt-1 text-sm text-slate-500">Consulte as vendas realizadas e visualize os detalhes de cada pedido.</p>
            </div>
            <button type="button" class="inline-flex items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-violet-600 to-violet-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-violet-200 transition hover:from-violet-700 hover:to-violet-800 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2" @click="openCreate">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14" /></svg>
                Nova venda
            </button>
        </div>

        @if (session('success'))
            <div class="flex items-center gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m5 12 4 4L19 6" /></svg>
                {{ session('success') }}
            </div>
        @endif

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 p-4">
                <form method="GET" action="{{ route('sales.index') }}" class="flex flex-col gap-3 sm:flex-row">
                    <div class="relative flex-1">
                        <label for="sale-search" class="sr-only">Buscar vendas</label>
                        <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35M19 11a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z" /></svg>
                        <input id="sale-search" type="search" name="search" value="{{ $search }}" placeholder="Buscar por número, cliente ou vendedor..." class="block w-full rounded-lg border-slate-300 py-2.5 pl-10 pr-3 text-sm shadow-sm placeholder:text-slate-400 focus:border-violet-500 focus:ring-violet-500">
                    </div>
                    <label for="sale-show-inactive" class="inline-flex shrink-0 cursor-pointer items-center gap-2 self-center px-1 py-2 text-sm font-medium text-slate-600">
                        <input id="sale-show-inactive" name="show_inactive" type="checkbox" value="1" @checked($showInactive) class="h-4 w-4 rounded border-slate-300 text-violet-600 focus:ring-violet-500" @change="$el.form.requestSubmit()">
                        Visualizar inativos
                    </label>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Nº</th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Data</th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Cliente</th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Vendedor</th>
                            <th scope="col" class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">Itens</th>
                            <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Total</th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Situação</th>
                            <th scope="col" class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($sales as $sale)
                            <tr
                                @class([
                                    'cursor-pointer transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-violet-500',
                                    'hover:bg-slate-50/80 focus-visible:bg-violet-50/50' => $sale->active,
                                    'bg-[#F5F5F5] [&>td]:bg-[#F5F5F5] hover:bg-[#EEEEEE] hover:[&>td]:bg-[#EEEEEE]' => ! $sale->active,
                                ])
                                tabindex="0"
                                aria-label="Visualizar venda {{ $sale->formattedNumber() }}"
                                aria-haspopup="dialog"
                                @click="openDetails(@js(route('sales.show', ['sale' => $sale->id])), $el)"
                                @keydown.enter.prevent.self="openDetails(@js(route('sales.show', ['sale' => $sale->id])), $el, true)"
                                @keydown.space.prevent.self="openDetails(@js(route('sales.show', ['sale' => $sale->id])), $el, true)"
                            >
                                <td class="whitespace-nowrap px-5 py-3.5 text-sm font-semibold text-slate-700">{{ $sale->formattedNumber() }}</td>
                                <td class="whitespace-nowrap px-5 py-3.5 text-sm text-slate-600">{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                                <td class="whitespace-nowrap px-5 py-3.5 text-sm font-medium text-slate-800">{{ $sale->customer?->name ?? '—' }}</td>
                                <td class="whitespace-nowrap px-5 py-3.5 text-sm text-slate-600">{{ $sale->seller?->name ?? '—' }}</td>
                                <td class="whitespace-nowrap px-5 py-3.5 text-center text-sm text-slate-600">{{ (int) $sale->items_quantity }}</td>
                                <td class="whitespace-nowrap px-5 py-3.5 text-right text-sm font-semibold text-slate-800">{{ $sale->formattedTotal() }}</td>
                                <td class="whitespace-nowrap px-5 py-3.5 text-sm"><x-active-status-badge :active="$sale->active" /></td>
                                <td class="whitespace-nowrap px-5 py-3.5 text-center">
                                    <div class="inline-flex items-center justify-center gap-1.5">
                                        <x-grid-action-button icon="pencil" variant="edit" title="Editar"
                                            data-details-url="{{ route('sales.show', ['sale' => $sale->id]) }}"
                                            data-update-url="{{ route('sales.update', ['sale' => $sale->id]) }}"
                                            @click.stop="openEdit($el.dataset.detailsUrl, $el.dataset.updateUrl)"
                                            aria-label="Editar venda {{ $sale->formattedNumber() }}" />
                                        @if ($sale->active)
                                            <x-grid-action-button icon="trash-2" variant="deactivate" title="Excluir"
                                                data-deactivate-action="{{ route('sales.deactivate', ['sale' => $sale->id]) }}" data-deactivate-name="{{ $sale->formattedNumber() }}" @click.stop="openDeactivate($el)" aria-label="Excluir venda {{ $sale->formattedNumber() }}" />
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="h-64 px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-violet-50 text-violet-600">
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.6L20 8H6M10 20h.01M17 20h.01" /></svg>
                                        </span>
                                        <p class="mt-4 text-sm font-semibold text-slate-700">Nenhuma venda encontrada</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($sales->isNotEmpty())
                <div class="flex flex-col gap-4 border-t border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-slate-500">Exibindo {{ $sales->firstItem() }} a {{ $sales->lastItem() }} de {{ $sales->total() }} registros</p>
                    @if ($sales->hasPages())
                        <div class="max-w-full">{{ $sales->links('sales.partials.pagination') }}</div>
                    @endif
                </div>
            @endif
        </section>

        <div x-cloak x-show="detailsOpen" x-transition:enter="transition-opacity ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="closeDetails" aria-labelledby="sale-modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-full items-center justify-center p-4 sm:p-6">
                <div class="fixed inset-0 bg-slate-950/50 backdrop-blur-sm" @click="closeDetails"></div>
                <section x-show="detailsOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2 scale-[0.98]" x-transition:enter-end="opacity-100 translate-y-0 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0 scale-100" x-transition:leave-end="opacity-0 translate-y-1 scale-[0.98]" class="relative flex max-h-[calc(100vh-2rem)] w-full max-w-5xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/20 sm:max-h-[calc(100vh-3rem)]">
                    <div class="flex shrink-0 items-center justify-between gap-4 border-b border-slate-200 px-5 py-4 sm:px-6">
                        <div class="min-w-0">
                            <h2 id="sale-modal-title" class="truncate text-lg font-bold text-slate-900" x-text="sale ? `Detalhes da venda ${sale.number}` : 'Detalhes da venda'"></h2>
                            <p x-show="sale" class="mt-0.5 truncate text-sm text-slate-500" x-text="sale ? `Realizada em ${sale.date.replace(' ', ' às ')}` : ''"></p>
                        </div>
                        <button x-ref="detailsClose" type="button" class="shrink-0 rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" @click="closeDetails" aria-label="Fechar modal"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18 18 6M6 6l12 12" /></svg></button>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6" aria-live="polite">
                        <div x-show="loading" class="flex min-h-48 items-center justify-center text-sm text-slate-500">Carregando detalhes...</div>
                        <div x-show="error" class="flex min-h-48 items-center justify-center text-sm font-medium text-rose-600" x-text="error"></div>

                        <div x-show="sale && !loading" class="space-y-5">
                            <dl class="grid gap-4 md:grid-cols-3">
                                <div>
                                    <dt class="mb-2 text-sm font-medium text-slate-700">Cliente</dt>
                                    <dd class="flex min-h-[42px] items-center rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm font-medium text-slate-800" x-text="sale?.customer.name"></dd>
                                </div>
                                <div>
                                    <dt class="mb-2 text-sm font-medium text-slate-700">Vendedor</dt>
                                    <dd class="flex min-h-[42px] items-center rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm font-medium text-slate-800" x-text="sale?.seller.name"></dd>
                                </div>
                                <div>
                                    <dt class="mb-2 text-sm font-medium text-slate-700">Situação</dt>
                                    <dd class="flex min-h-[42px] items-center rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2">
                                        <x-active-status-badge model="sale?.active" />
                                    </dd>
                                </div>
                            </dl>

                            <div class="overflow-x-auto rounded-xl border border-slate-200">
                                <table class="min-w-full divide-y divide-slate-200">
                                    <thead class="bg-slate-50"><tr>
                                        <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Imagem</th>
                                        <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Código</th>
                                        <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Produto</th>
                                        <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">Quantidade</th>
                                        <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Valor unitário</th>
                                        <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Subtotal</th>
                                    </tr></thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        <template x-for="item in sale?.items ?? []" :key="item.sale_item_id">
                                            <tr>
                                                <td class="px-3 py-2.5">
                                                    <div class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-lg bg-slate-100">
                                                        <img x-show="item.photo_url" :src="item.photo_url" class="h-full w-full object-cover" :alt="`Imagem de ${item.product}`">
                                                        <svg x-show="! item.photo_url" class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M3 16.5 8.5 11l4 4L15 12.5l6 6M5 20h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z" /></svg>
                                                    </div>
                                                </td>
                                                <td class="whitespace-nowrap px-3 py-2.5 text-sm text-slate-600" x-text="item.sku"></td>
                                                <td class="px-3 py-2.5 text-sm font-medium text-slate-800" x-text="item.product"></td>
                                                <td class="px-3 py-2.5 text-center text-sm text-slate-600" x-text="item.quantity"></td>
                                                <td class="whitespace-nowrap px-3 py-2.5 text-right text-sm text-slate-600" x-text="item.unit_price"></td>
                                                <td class="whitespace-nowrap px-3 py-2.5 text-right text-sm font-semibold text-slate-800" x-text="item.total"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div x-show="sale && !loading" class="flex shrink-0 items-center justify-end gap-4 border-t border-slate-200 bg-slate-50/70 px-5 py-4 sm:px-6">
                        <span class="text-sm font-semibold text-slate-600">Total</span>
                        <strong class="text-xl text-slate-900" x-text="sale?.total"></strong>
                    </div>
                </section>
            </div>
        </div>

        <div x-cloak x-show="formOpen" x-transition:enter="transition-opacity ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="closeForm" aria-labelledby="sale-form-modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-full items-center justify-center p-4 sm:p-6">
                <div class="fixed inset-0 bg-slate-950/50 backdrop-blur-sm" @click="closeForm"></div>
                <section x-show="formOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2 scale-[0.98]" x-transition:enter-end="opacity-100 translate-y-0 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0 scale-100" x-transition:leave-end="opacity-0 translate-y-1 scale-[0.98]" class="relative w-full max-w-5xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/20">
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 sm:px-6">
                        <h2 id="sale-form-modal-title" class="text-lg font-bold text-slate-900" x-text="mode === 'edit' ? `Editar venda #${String(saleId).padStart(6, '0')}` : 'Nova venda'"></h2>
                        <button type="button" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700" @click="closeForm" aria-label="Fechar modal"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18 18 6M6 6l12 12" /></svg></button>
                    </div>
                    @include('sales.partials.form')
                </section>
            </div>
        </div>

        <x-deactivate-confirmation-modal
            id="sale-deactivate-modal"
            title="Excluir venda?"
            subject="a venda"
            description="A venda deixará de aparecer na consulta padrão, mas seu histórico será preservado integralmente."
        />
    </div>
</x-app-layout>
