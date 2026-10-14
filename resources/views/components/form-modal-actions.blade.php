@props(['createLabel'])

<div class="flex flex-col-reverse gap-3 border-t border-slate-200 bg-slate-50/70 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
    <button
        type="button"
        :disabled="submitting"
        class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 disabled:cursor-wait disabled:opacity-60"
        @click="closeModal"
    >
        Cancelar
    </button>

    <button
        type="submit"
        :disabled="submitting"
        x-bind:aria-busy="submitting"
        class="inline-flex min-h-[2.625rem] min-w-40 shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-lg bg-gradient-to-r from-violet-600 to-violet-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-violet-200 transition hover:from-violet-700 hover:to-violet-800 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-70"
    >
        <svg x-show="! submitting" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4 4L19 6" />
        </svg>
        <svg x-cloak x-show="submitting" class="h-4 w-4 shrink-0 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" />
            <path class="opacity-90" fill="currentColor" d="M21 12a9 9 0 0 0-9-9v3a6 6 0 0 1 6 6h3Z" />
        </svg>

        <span x-show="! submitting && mode !== 'edit'">{{ $createLabel }}</span>
        <span x-cloak x-show="! submitting && mode === 'edit'">Salvar alterações</span>
        <span x-cloak x-show="submitting">Salvando...</span>
    </button>
</div>
