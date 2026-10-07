@php
    $oldProductId = (string) old('product_id', '');
    $isEditing = old('form_mode') === 'edit' && ctype_digit($oldProductId);
    $formAction = $isEditing ? route('products.update', ['product' => $oldProductId]) : route('products.store');
    $formConfig = [
        'open' => $errors->any(),
        'mode' => $isEditing ? 'edit' : 'create',
        'formAction' => $formAction,
        'storeUrl' => route('products.store'),
        'productId' => $oldProductId,
        'photoUrl' => '',
        'removePhoto' => old('remove_photo') === '1',
        'fields' => [
            'sku' => (string) old('sku', ''),
            'name' => (string) old('name', ''),
            'sale_price' => (string) old('sale_price', ''),
            'active' => (string) old('active', '1'),
        ],
    ];
@endphp

<x-app-layout>
    <x-slot name="title">Produtos | Infinit ERP</x-slot>

    <div x-data="productForm({{ Js::from($formConfig) }})" class="mx-auto max-w-7xl space-y-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Produtos</h1>
                <p class="mt-1 text-sm text-slate-500">Cadastre e gerencie seus produtos.</p>
            </div>
            <button type="button" class="inline-flex items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-violet-600 to-violet-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-violet-200 transition hover:from-violet-700 hover:to-violet-800 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2" @click="openCreate">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14" /></svg>
                Novo produto
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
                <form method="GET" action="{{ route('products.index') }}" class="flex flex-col gap-3 sm:flex-row">
                    <div class="relative flex-1">
                        <label for="product-search" class="sr-only">Buscar produtos</label>
                        <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35M19 11a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z" /></svg>
                        <input id="product-search" type="search" name="search" value="{{ $search }}" placeholder="Buscar por código ou nome..." class="block w-full rounded-lg border-slate-300 py-2.5 pl-10 pr-3 text-sm shadow-sm placeholder:text-slate-400 focus:border-violet-500 focus:ring-violet-500">
                    </div>
                    <label for="product-show-inactive" class="inline-flex shrink-0 cursor-pointer items-center gap-2 self-center px-1 py-2 text-sm font-medium text-slate-600">
                        <input id="product-show-inactive" name="show_inactive" type="checkbox" value="1" @checked($showInactive) class="h-4 w-4 rounded border-slate-300 text-violet-600 focus:ring-violet-500" @change="$el.form.requestSubmit()">
                        Visualizar inativos
                    </label>
                    <button type="submit" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-violet-300 hover:text-violet-700">Buscar</button>
                </form>
            </div>

            @if ($products->isEmpty())
                <div class="flex min-h-72 flex-col items-center justify-center px-6 py-12 text-center">
                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-violet-50 text-violet-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m4 7 8-4 8 4-8 4-8-4Zm0 0v10l8 4 8-4V7M12 11v10" /></svg>
                    </span>
                    <p class="mt-4 font-semibold text-slate-800">{{ $search !== '' ? 'Nenhum produto encontrado' : 'Nenhum produto cadastrado' }}</p>
                    <p class="mt-1 max-w-sm text-sm text-slate-500">{{ $search !== '' ? 'Tente buscar usando outro código ou nome.' : 'Cadastre o primeiro produto para começar.' }}</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50"><tr>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Código</th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Nome</th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Preço de venda</th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Situação</th>
                            <th scope="col" class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">Ações</th>
                        </tr></thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($products as $product)
                                <tr class="transition hover:bg-slate-50/80">
                                    <td class="whitespace-nowrap px-5 py-3.5 text-sm font-medium text-slate-700">{{ $product->sku }}</td>
                                    <td class="whitespace-nowrap px-5 py-3.5 text-sm font-medium text-slate-800">{{ $product->name }}</td>
                                    <td class="whitespace-nowrap px-5 py-3.5 text-sm text-slate-600">{{ $product->formattedSalePrice() }}</td>
                                    <td class="whitespace-nowrap px-5 py-3.5 text-sm">
                                        <x-active-status-badge :active="$product->active" />
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3.5 text-center">
                                        <div class="inline-flex items-center justify-center gap-1.5">
                                            <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-violet-600 transition hover:bg-violet-50 hover:text-violet-800 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-1"
                                                data-id="{{ $product->id }}" data-sku="{{ $product->sku }}" data-name="{{ $product->name }}" data-sale-price="{{ $product->sale_price }}" data-active="{{ $product->active ? '1' : '0' }}" data-photo-url="{{ $product->photoUrl() }}" data-action="{{ route('products.update', ['product' => $product->id]) }}" @click="openEdit($el)" title="Editar" aria-label="Editar produto {{ $product->name }}">
                                                <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m14.7 6.3 3 3M5 19l3.6-.7L19 7.9a2.1 2.1 0 0 0-3-3L5.7 15.3 5 19Z" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-col gap-4 border-t border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-slate-500">Exibindo {{ $products->firstItem() }} a {{ $products->lastItem() }} de {{ $products->total() }} registros</p>
                    @if ($products->hasPages())<div class="max-w-full">{{ $products->links('products.partials.pagination') }}</div>@endif
                </div>
            @endif
        </section>

        <div x-cloak x-show="modalOpen" x-transition:enter="transition-opacity ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="closeModal" aria-labelledby="product-modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-full items-center justify-center p-4 sm:p-6">
                <div class="fixed inset-0 bg-slate-950/50 backdrop-blur-sm" @click="closeModal"></div>
                <section x-show="modalOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2 scale-[0.98]" x-transition:enter-end="opacity-100 translate-y-0 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0 scale-100" x-transition:leave-end="opacity-0 translate-y-1 scale-[0.98]" class="relative w-full max-w-3xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/20">
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 sm:px-6">
                        <h2 id="product-modal-title" class="text-lg font-bold text-slate-900" x-text="mode === 'edit' ? 'Editar produto' : 'Adicionar produto'"></h2>
                        <button type="button" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700" @click="closeModal" aria-label="Fechar modal"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18 18 6M6 6l12 12" /></svg></button>
                    </div>
                    @include('products.partials.form')
                </section>
            </div>
        </div>

    </div>
</x-app-layout>
