@props([
    'id',
    'title',
    'subject',
    'description',
])

<div
    x-data="{ submitting: false }"
    x-cloak
    x-show="deactivateOpen"
    x-effect="if (!deactivateOpen) submitting = false"
    x-transition:enter="transition-opacity ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-[60] overflow-y-auto"
    @keydown.escape.window="closeDeactivate"
    aria-labelledby="{{ $id }}-title"
    role="dialog"
    aria-modal="true"
>
    <div class="flex min-h-full items-center justify-center p-4 sm:p-6">
        <div class="fixed inset-0 bg-slate-950/50 backdrop-blur-sm" @click="closeDeactivate"></div>

        <section
            x-show="deactivateOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2 scale-[0.98]"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-1 scale-[0.98]"
            class="relative w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/20"
        >
            <div class="px-5 py-5 sm:px-6">
                <div class="flex items-start gap-4">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-rose-50 text-rose-600">
                        <svg class="h-[22px] w-[22px]" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M3 6h18" />
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" />
                            <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                            <path d="M10 11v6" />
                            <path d="M14 11v6" />
                        </svg>
                    </span>

                    <div>
                        <h2 id="{{ $id }}-title" class="text-lg font-bold text-slate-900">{{ $title }}</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Tem certeza que deseja excluir {{ $subject }} “<strong class="font-semibold text-slate-800" x-text="deactivateName"></strong>”?
                        </p>
                        <p class="mt-2 text-sm leading-6 text-slate-500">{{ $description }}</p>
                    </div>
                </div>
            </div>

            <form method="POST" x-bind:action="deactivateAction" class="flex flex-col-reverse gap-3 border-t border-slate-200 bg-slate-50/70 px-5 py-4 sm:flex-row sm:justify-end sm:px-6" @submit="submitting = true">
                @csrf
                @method('PATCH')
                <button type="button" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2" @click="closeDeactivate">
                    Cancelar
                </button>
                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-70" :disabled="submitting" x-text="submitting ? 'Excluindo...' : 'Excluir'">
                    Excluir
                </button>
            </form>
        </section>
    </div>
</div>
