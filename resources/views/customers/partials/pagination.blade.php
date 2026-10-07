@if ($paginator->hasPages())
    <nav class="flex max-w-full justify-center sm:justify-end" role="navigation" aria-label="Paginação">
        <div class="inline-flex flex-wrap items-center justify-center gap-1">
            @if ($paginator->onFirstPage())
                <span
                    class="inline-flex h-8 w-8 cursor-not-allowed items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-300"
                    aria-disabled="true"
                    aria-label="Página anterior"
                    title="Anterior"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 18-6-6 6-6" />
                    </svg>
                </span>
            @else
                <a
                    href="{{ $paginator->previousPageUrl() }}"
                    rel="prev"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:border-violet-300 hover:bg-violet-50 hover:text-violet-700 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-1"
                    aria-label="Página anterior"
                    title="Anterior"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 18-6-6 6-6" />
                    </svg>
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="inline-flex h-8 min-w-8 items-center justify-center px-1 text-sm text-slate-400" aria-hidden="true">
                        {{ $element }}
                    </span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span
                                class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg border border-violet-600 bg-violet-600 px-2 text-sm font-semibold text-white shadow-sm shadow-violet-200"
                                aria-current="page"
                                aria-label="Página {{ $page }}"
                            >
                                {{ $page }}
                            </span>
                        @else
                            <a
                                href="{{ $url }}"
                                class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg border border-slate-200 bg-white px-2 text-sm font-medium text-slate-600 transition hover:border-violet-300 hover:bg-violet-50 hover:text-violet-700 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-1"
                                aria-label="Ir para a página {{ $page }}"
                            >
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a
                    href="{{ $paginator->nextPageUrl() }}"
                    rel="next"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:border-violet-300 hover:bg-violet-50 hover:text-violet-700 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-1"
                    aria-label="Próxima página"
                    title="Próxima"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 18 6-6-6-6" />
                    </svg>
                </a>
            @else
                <span
                    class="inline-flex h-8 w-8 cursor-not-allowed items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-300"
                    aria-disabled="true"
                    aria-label="Próxima página"
                    title="Próxima"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 18 6-6-6-6" />
                    </svg>
                </span>
            @endif
        </div>
    </nav>
@endif
