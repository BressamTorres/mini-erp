<x-app-layout>
    <x-slot name="title">Meu Catálogo Online | Infinit ERP</x-slot>

    <div class="mx-auto max-w-[61.625rem] space-y-5">
        <header class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Meu Catálogo Online</h1>
                <p class="mt-1 text-sm text-slate-500">Compartilhe seu catálogo com seus clientes e receba pedidos online.</p>
            </div>
        </header>

        @if ($catalogUrl !== null)
            <div x-data="catalogSharing(@js($catalogUrl))" class="grid grid-cols-1 items-start gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(20rem,27.5rem)] xl:items-stretch">
                <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="p-6 sm:p-7">
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-violet-50 text-violet-600">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10 13a5 5 0 0 0 7.1.1l2-2a5 5 0 0 0-7.1-7.1l-1.1 1.1M14 11a5 5 0 0 0-7.1-.1l-2 2A5 5 0 0 0 12 20l1.1-1.1" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-slate-900">Endereço do catálogo</h2>
                                <p class="mt-1 text-sm text-slate-500">Link público de {{ $business->trade_name }}.</p>
                            </div>
                        </div>

                        <div class="mt-5 rounded-lg border border-violet-200 bg-violet-50/60 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-violet-700">URL pública</p>
                            <a href="{{ $catalogUrl }}" target="_blank" rel="noopener noreferrer" class="mt-1 block break-all text-sm font-semibold text-violet-700 underline decoration-violet-300 underline-offset-4 transition hover:text-violet-800 hover:decoration-violet-500 sm:text-base">
                                {{ $catalogUrl }}
                            </a>
                        </div>

                        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <button type="button" @click="copyLink" class="inline-flex min-h-[2.625rem] items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-violet-600 to-violet-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-violet-200 transition hover:from-violet-700 hover:to-violet-800 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 8h11v11H8V8Zm-3 8H4V4h12v1" />
                                </svg>
                                Copiar link
                            </button>

                            <a href="{{ $catalogUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-[2.625rem] items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-violet-300 hover:bg-violet-50 hover:text-violet-700 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 4h6v6m0-6-9 9M18 13v6a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h6" />
                                </svg>
                                Abrir catálogo
                            </a>
                        </div>

                        <p x-cloak x-show="copied" class="mt-3 text-sm font-medium text-emerald-700" role="status">Link copiado para a área de transferência.</p>
                        <p x-cloak x-show="copyFailed" class="mt-3 text-sm font-medium text-rose-700" role="alert">Não foi possível copiar automaticamente. Selecione o endereço acima.</p>
                    </div>
                </section>

                <aside class="flex items-center justify-center rounded-xl border border-slate-200 bg-white p-4 text-center shadow-sm">
                    @if ($qrCodeUrl !== null)
                        <div class="inline-flex rounded-lg border border-slate-200 bg-white p-4">
                            <img src="{{ $qrCodeUrl }}" alt="QR Code para {{ $catalogUrl }}" width="260" height="260" class="aspect-square h-auto w-[12.5rem] max-w-full object-contain">
                        </div>
                    @else
                        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            O serviço de QR Code não está configurado.
                        </div>
                    @endif
                </aside>
            </div>
        @else
            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="flex flex-col items-center text-center">
                    <span class="flex h-14 w-14 items-center justify-center rounded-full bg-violet-50 text-violet-600">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v4m0 4h.01M10.3 4.3 2.8 17.2A2 2 0 0 0 4.5 20h15a2 2 0 0 0 1.7-2.8L13.7 4.3a2 2 0 0 0-3.4 0Z" />
                        </svg>
                    </span>
                    <h2 class="mt-4 text-lg font-semibold text-slate-900">Catálogo indisponível</h2>
                    @if (blank($business->catalog_slug))
                        <p class="mt-2 max-w-lg text-sm text-slate-600">Sua empresa ainda não possui um endereço de catálogo. Entre em contato com o suporte.</p>
                    @else
                        <p class="mt-2 max-w-lg text-sm text-slate-600">O domínio público do catálogo ainda não está configurado. Entre em contato com o suporte.</p>
                    @endif
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
