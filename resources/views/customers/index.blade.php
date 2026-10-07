@php
    $oldCustomerId = (string) old('customer_id', '');
    $isEditing = old('form_mode') === 'edit' && ctype_digit($oldCustomerId);
    $formAction = $isEditing
        ? route('customers.update', ['customer' => $oldCustomerId])
        : route('customers.store');
    $formConfig = [
        'open' => $errors->any(),
        'mode' => $isEditing ? 'edit' : 'create',
        'formAction' => $formAction,
        'storeUrl' => route('customers.store'),
        'customerId' => $oldCustomerId,
        'photoUrl' => '',
        'removePhoto' => old('remove_photo') === '1',
        'fields' => [
            'name' => (string) old('name', ''),
            'document' => (string) old('document', ''),
            'phone' => (string) old('phone', ''),
            'email' => (string) old('email', ''),
        ],
    ];
@endphp

<x-app-layout>
    <x-slot name="title">Clientes | Infinit ERP</x-slot>

    <div x-data="customerForm({{ Js::from($formConfig) }})" class="mx-auto max-w-7xl space-y-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Clientes</h1>
                <p class="mt-1 text-sm text-slate-500">Cadastre e gerencie seus clientes.</p>
            </div>

            <button
                type="button"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-violet-600 to-violet-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-violet-200 transition hover:from-violet-700 hover:to-violet-800 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2"
                @click="openCreate"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14" />
                </svg>
                Novo cliente
            </button>
        </div>

        @if (session('success'))
            <div class="flex items-center gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m5 12 4 4L19 6" />
                </svg>
                {{ session('success') }}
            </div>
        @endif

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 p-4">
                <form method="GET" action="{{ route('customers.index') }}" class="flex flex-col gap-3 sm:flex-row">
                    <div class="relative flex-1">
                        <label for="customer-search" class="sr-only">Buscar clientes</label>
                        <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35M19 11a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z" />
                        </svg>
                        <input
                            id="customer-search"
                            type="search"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Buscar por nome, CPF/CNPJ, telefone ou e-mail..."
                            class="block w-full rounded-lg border-slate-300 py-2.5 pl-10 pr-3 text-sm shadow-sm placeholder:text-slate-400 focus:border-violet-500 focus:ring-violet-500"
                        >
                    </div>

                    <button type="submit" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-violet-300 hover:text-violet-700">
                        Buscar
                    </button>

                    @if ($search !== '')
                        <a href="{{ route('customers.index') }}" class="inline-flex items-center justify-center rounded-lg px-3 py-2.5 text-sm font-medium text-slate-500 hover:bg-slate-50 hover:text-violet-700">
                            Limpar
                        </a>
                    @endif
                </form>
            </div>

            @if ($customers->isEmpty())
                <div class="flex min-h-72 flex-col items-center justify-center px-6 py-12 text-center">
                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-violet-50 text-violet-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 19v-1a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v1M10 10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm8 1a3 3 0 0 1 3 3v1M16 3.2a3 3 0 0 1 0 5.6" />
                        </svg>
                    </span>
                    <p class="mt-4 font-semibold text-slate-800">
                        {{ $search !== '' ? 'Nenhum cliente encontrado' : 'Nenhum cliente cadastrado' }}
                    </p>
                    <p class="mt-1 max-w-sm text-sm text-slate-500">
                        {{ $search !== '' ? 'Tente buscar usando outro nome, documento, telefone ou e-mail.' : 'Cadastre o primeiro cliente para começar.' }}
                    </p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Nome</th>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">CPF/CNPJ</th>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Telefone</th>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">E-mail</th>
                                <th scope="col" class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($customers as $customer)
                                <tr class="transition hover:bg-slate-50/80">
                                    <td class="whitespace-nowrap px-5 py-3.5 text-sm font-medium text-slate-800">{{ $customer->name }}</td>
                                    <td class="whitespace-nowrap px-5 py-3.5 text-sm text-slate-600">{{ $customer->formattedDocument() ?: '—' }}</td>
                                    <td class="whitespace-nowrap px-5 py-3.5 text-sm text-slate-600">{{ $customer->formattedPhone() }}</td>
                                    <td class="whitespace-nowrap px-5 py-3.5 text-sm text-slate-600">{{ $customer->email ?: '—' }}</td>
                                    <td class="whitespace-nowrap px-5 py-3.5 text-center">
                                        <div class="inline-flex items-center justify-center gap-1.5">
                                            <button
                                                type="button"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-violet-600 transition hover:bg-violet-50 hover:text-violet-800 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-1"
                                                data-id="{{ $customer->id }}"
                                                data-name="{{ $customer->name }}"
                                                data-document="{{ $customer->document }}"
                                                data-phone="{{ $customer->phone }}"
                                                data-email="{{ $customer->email }}"
                                                data-photo-url="{{ $customer->photoUrl() }}"
                                                data-action="{{ route('customers.update', ['customer' => $customer->id]) }}"
                                                @click="openEdit($el)"
                                                title="Editar"
                                                aria-label="Editar cliente {{ $customer->name }}"
                                            >
                                                <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m14.7 6.3 3 3M5 19l3.6-.7L19 7.9a2.1 2.1 0 0 0-3-3L5.7 15.3 5 19Z" />
                                                </svg>
                                            </button>

                                            <button
                                                type="button"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-rose-600 transition hover:bg-rose-50 hover:text-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-1"
                                                data-name="{{ $customer->name }}"
                                                data-action="{{ route('customers.destroy', ['customer' => $customer->id]) }}"
                                                @click="openDelete($el)"
                                                title="Excluir"
                                                aria-label="Excluir cliente {{ $customer->name }}"
                                            >
                                                <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7h16M9 7V4h6v3m-8 0 1 13h8l1-13M10 11v5m4-5v5" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col gap-4 border-t border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-slate-500">
                        Exibindo {{ $customers->firstItem() }} a {{ $customers->lastItem() }} de {{ $customers->total() }} registros
                    </p>

                    @if ($customers->hasPages())
                        <div class="max-w-full">{{ $customers->links('customers.partials.pagination') }}</div>
                    @endif
                </div>
            @endif
        </section>

        <div
            x-cloak
            x-show="modalOpen"
            x-transition:enter="transition-opacity ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 overflow-y-auto"
            @keydown.escape.window="closeModal"
            aria-labelledby="customer-modal-title"
            role="dialog"
            aria-modal="true"
        >
            <div class="flex min-h-full items-center justify-center p-4 sm:p-6">
                <div class="fixed inset-0 bg-slate-950/50 backdrop-blur-sm" @click="closeModal"></div>

                <section
                    x-show="modalOpen"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-2 scale-[0.98]"
                    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                    x-transition:leave-end="opacity-0 translate-y-1 scale-[0.98]"
                    class="relative w-full max-w-3xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/20"
                >
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 sm:px-6">
                        <h2 id="customer-modal-title" class="text-lg font-bold text-slate-900" x-text="mode === 'edit' ? 'Editar cliente' : 'Adicionar cliente'"></h2>
                        <button type="button" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700" @click="closeModal" aria-label="Fechar modal">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    @include('customers.partials.form')
                </section>
            </div>
        </div>

        <div
            x-cloak
            x-show="deleteModalOpen"
            x-transition:enter="transition-opacity ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 overflow-y-auto"
            @keydown.escape.window="closeDeleteModal"
            aria-labelledby="delete-customer-modal-title"
            role="dialog"
            aria-modal="true"
        >
            <div class="flex min-h-full items-center justify-center p-4 sm:p-6">
                <div class="fixed inset-0 bg-slate-950/50 backdrop-blur-sm" @click="closeDeleteModal"></div>

                <section
                    x-show="deleteModalOpen"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-2 scale-[0.98]"
                    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                    x-transition:leave-end="opacity-0 translate-y-1 scale-[0.98]"
                    class="relative w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/20"
                >
                    <form method="POST" x-bind:action="deleteAction">
                        @csrf
                        @method('DELETE')

                        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 sm:px-6">
                            <h2 id="delete-customer-modal-title" class="text-lg font-bold text-slate-900">Excluir cliente</h2>
                            <button type="button" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700" @click="closeDeleteModal" aria-label="Fechar modal">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="space-y-2 px-5 py-6 sm:px-6">
                            <p class="text-sm leading-6 text-slate-700">
                                Tem certeza que deseja excluir o cliente
                                <span class="font-semibold text-slate-900">&quot;<span x-text="deleteCustomerName"></span>&quot;</span>?
                            </p>
                            <p class="text-sm text-slate-500">Esta ação não poderá ser desfeita.</p>
                        </div>

                        <div class="flex flex-col-reverse gap-3 border-t border-slate-200 bg-slate-50/70 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                            <button
                                x-ref="deleteCancel"
                                type="button"
                                class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                                @click="closeDeleteModal"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                class="rounded-lg bg-rose-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-rose-200 transition hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2"
                            >
                                Excluir cliente
                            </button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
