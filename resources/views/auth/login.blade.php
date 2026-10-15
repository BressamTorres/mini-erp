<x-guest-layout>
    <div class="mb-8 text-center">
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">
            <span class="text-violet-600">Infinit</span> ERP
        </h1>
        <p class="mt-2 text-sm text-slate-500">Acesse sua conta</p>
    </div>

    <x-auth-session-status class="mb-5" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" x-data="{ showPassword: false }" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="mb-2 block text-sm font-medium text-slate-700">E-mail</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
                placeholder="seu@email.com"
                class="block w-full rounded-lg border-slate-300 px-3.5 py-2.5 text-sm shadow-sm placeholder:text-slate-400 focus:border-violet-500 focus:ring-violet-500"
            >
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <label for="password" class="mb-2 block text-sm font-medium text-slate-700">Senha</label>
            <div class="relative">
                <input
                    id="password"
                    :type="showPassword ? 'text' : 'password'"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="Digite sua senha"
                    class="block w-full rounded-lg border-slate-300 py-2.5 pl-3.5 pr-11 text-sm shadow-sm placeholder:text-slate-400 focus:border-violet-500 focus:ring-violet-500"
                >
                <button
                    type="button"
                    class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-slate-400 hover:text-violet-600"
                    @click="showPassword = ! showPassword"
                    :aria-label="showPassword ? 'Ocultar senha' : 'Mostrar senha'"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" />
                        <circle cx="12" cy="12" r="2.5" stroke-width="1.8" />
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <label for="remember_me" class="inline-flex cursor-pointer items-center">
                <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-violet-600 shadow-sm focus:ring-violet-500" name="remember">
                <span class="ml-2 text-sm text-slate-600">Lembrar de mim</span>
            </label>
        </div>

        <button type="submit" class="flex w-full justify-center rounded-lg bg-gradient-to-r from-violet-600 to-violet-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-violet-200 transition hover:from-violet-700 hover:to-violet-800 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2">
            Entrar
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
        Ainda não tem conta?
        <a href="{{ route('register') }}" class="font-semibold text-violet-600 hover:text-violet-700 focus:outline-none focus:underline">
            Criar conta
        </a>
    </p>
</x-guest-layout>
