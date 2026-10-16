<x-app-layout>
    <x-slot name="title">Meu Catálogo Online | Infinit ERP</x-slot>

    <div class="mx-auto max-w-6xl space-y-6">
        <header>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Meu Catálogo Online</h1>
            <p class="mt-1 text-sm text-slate-500">Compartilhe seu catálogo com seus clientes e receba pedidos online.</p>
        </header>

        @if ($catalogUrl !== null)
            <div x-data="catalogSharing(@js($catalogUrl))" class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
                <section class="overflow-hidden rounded-xl border border-blue-200 bg-white shadow-sm">
                    <div class="bg-gradient-to-br from-blue-600 to-blue-700 px-5 py-6 text-white sm:px-7">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/20">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10 13a5 5 0 0 0 7.1.1l2-2a5 5 0 0 0-7.1-7.1l-1.1 1.1M14 11a5 5 0 0 0-7.1-.1l-2 2A5 5 0 0 0 12 20l1.1-1.1" />
                            </svg>
                        </div>
                        <p class="mt-4 text-sm font-medium text-blue-100">Endereço público de {{ $business->trade_name }}</p>
                        <a href="{{ $catalogUrl }}" target="_blank" rel="noopener noreferrer" class="mt-1 block break-all text-lg font-semibold underline decoration-blue-300 underline-offset-4 hover:decoration-white">
                            {{ $catalogUrl }}
                        </a>
                    </div>

                    <div class="p-5 sm:p-7">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <button type="button" @click="copyLink" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 8h11v11H8V8Zm-3 8H4V4h12v1" />
                                </svg>
                                Copiar link
                            </button>

                            <a href="{{ $catalogUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 4h6v6m0-6-9 9M18 13v6a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h6" />
                                </svg>
                                Abrir catálogo
                            </a>

                            <a href="{{ $whatsAppUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 11.5a8 8 0 0 1-11.8 7L4 20l1.5-4.1A8 8 0 1 1 20 11.5Z" />
                                </svg>
                                WhatsApp
                            </a>
                        </div>

                        <p x-cloak x-show="copied" class="mt-4 text-sm font-medium text-emerald-700" role="status">Link copiado para a área de transferência.</p>
                        <p x-cloak x-show="copyFailed" class="mt-4 text-sm font-medium text-rose-700" role="alert">Não foi possível copiar automaticamente. Selecione o endereço acima.</p>
                    </div>
                </section>

                <aside class="flex flex-col items-center rounded-xl border border-slate-200 bg-white p-6 text-center shadow-sm">
                    <h2 class="font-semibold text-slate-900">QR Code do catálogo</h2>
                    <p class="mt-1 text-sm text-slate-500">Aponte a câmera do celular para acessar.</p>

                    @if ($qrCodeUrl !== null)
                        <div class="mt-5 rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                            <img src="{{ $qrCodeUrl }}" alt="QR Code para {{ $catalogUrl }}" width="260" height="260" class="h-auto w-full max-w-[16.25rem]">
                        </div>
                    @else
                        <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            O serviço de QR Code não está configurado.
                        </div>
                    @endif
                </aside>
            </div>
        @else
            <section class="rounded-xl border border-amber-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="flex flex-col items-center text-center">
                    <span class="flex h-14 w-14 items-center justify-center rounded-full bg-amber-100 text-amber-700">
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
