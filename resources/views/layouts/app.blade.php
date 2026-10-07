<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? 'Infinit ERP' }}</title>

        <script>
            try {
                document.documentElement.dataset.sidebarCollapsed =
                    localStorage.getItem('infinit-erp-sidebar-collapsed') === 'true' ? 'true' : 'false';
            } catch (error) {
                document.documentElement.dataset.sidebarCollapsed = 'false';
            }
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-50 font-sans text-slate-900 antialiased">
        <div
            x-data="{
                sidebarOpen: false,
                sidebarCollapsed: document.documentElement.dataset.sidebarCollapsed === 'true',
                toggleSidebar() {
                    this.sidebarCollapsed = ! this.sidebarCollapsed;
                    document.documentElement.dataset.sidebarCollapsed = String(this.sidebarCollapsed);

                    try {
                        localStorage.setItem('infinit-erp-sidebar-collapsed', String(this.sidebarCollapsed));
                    } catch (error) {
                        // Mantém o estado durante a navegação atual quando o storage não está disponível.
                    }
                },
            }"
            class="min-h-screen"
        >
            @include('layouts.sidebar')

            <div
                class="erp-app-content min-h-screen transition-[padding] duration-200 ease-out lg:pl-64"
            >
                @include('layouts.topbar')

                <main class="p-4 sm:p-6 lg:p-8">
                    @isset($header)
                        <div class="mx-auto mb-6 max-w-7xl">
                            {{ $header }}
                        </div>
                    @endisset

                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
