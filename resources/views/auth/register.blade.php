<x-guest-layout title="Criar conta">
    <div class="mb-8 text-center">
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">
            <span class="text-violet-600">Infinit</span> ERP
        </h1>
        <p class="mt-2 text-sm text-slate-500">Crie sua conta</p>
    </div>

    <form method="POST" action="{{ route('register') }}" x-data="{ showPassword: false, showPasswordConfirmation: false }" class="space-y-5">
        @csrf

        <div>
            <label for="name" class="mb-2 block text-sm font-medium text-slate-700">Nome completo</label>
            <input
                id="name"
                type="text"
                name="name"
                value="{{ old('name') }}"
                required
                autofocus
                autocomplete="name"
                placeholder="Seu nome completo"
                class="block w-full rounded-lg border-slate-300 px-3.5 py-2.5 text-sm shadow-sm placeholder:text-slate-400 focus:border-violet-500 focus:ring-violet-500"
            >
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <label for="email" class="mb-2 block text-sm font-medium text-slate-700">E-mail</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
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
                    autocomplete="new-password"
                    placeholder="Mínimo de 8 caracteres"
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
            <label for="password_confirmation" class="mb-2 block text-sm font-medium text-slate-700">Confirmar senha</label>
            <div class="relative">
                <input
                    id="password_confirmation"
                    :type="showPasswordConfirmation ? 'text' : 'password'"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="Repita sua senha"
                    class="block w-full rounded-lg border-slate-300 py-2.5 pl-3.5 pr-11 text-sm shadow-sm placeholder:text-slate-400 focus:border-violet-500 focus:ring-violet-500"
                >
                <button
                    type="button"
                    class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-slate-400 hover:text-violet-600"
                    @click="showPasswordConfirmation = ! showPasswordConfirmation"
                    :aria-label="showPasswordConfirmation ? 'Ocultar confirmação da senha' : 'Mostrar confirmação da senha'"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" />
                        <circle cx="12" cy="12" r="2.5" stroke-width="1.8" />
                    </svg>
                </button>
            </div>
        </div>

        <button type="submit" class="flex w-full justify-center rounded-lg bg-gradient-to-r from-violet-600 to-violet-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-violet-200 transition hover:from-violet-700 hover:to-violet-800 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2">
            Criar conta
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
        Já possui uma conta?
        <a href="{{ route('login') }}" class="font-semibold text-violet-600 hover:text-violet-700 focus:outline-none focus:underline">
            Entrar
        </a>
    </p>
</x-guest-layout>
