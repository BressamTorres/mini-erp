<x-guest-layout :content-only="true">
    <div class="mx-auto max-w-sm rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
        <div class="mb-7 text-center">
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">
                <span class="text-violet-600">Infinit</span> ERP
            </h1>
            <p class="mt-2 text-sm text-slate-600">Acesse sua conta</p>
        </div>

        <x-auth-session-status class="mb-5" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}" x-data="{ showPassword: false, submitting: false }"
            x-on:submit="if (submitting) { $event.preventDefault(); } else { submitting = true; }">
            @csrf

            <div>
                <label for="email" class="mb-2 block text-sm font-semibold text-slate-800">E-mail</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                    autocomplete="username" placeholder="seu@email.com"
                    class="h-12 w-full rounded-xl border bg-white px-4 text-sm text-slate-950 outline-none transition placeholder:text-slate-400 focus:ring-2 {{ $errors->has('email') ? 'border-red-400 focus:border-red-500 focus:ring-red-100' : 'border-slate-300 focus:border-violet-500 focus:ring-violet-200' }}">
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div class="mt-5">
                <label for="password" class="mb-2 block text-sm font-semibold text-slate-800">Senha</label>
                <div class="relative">
                    <input id="password" name="password" x-bind:type="showPassword ? 'text' : 'password'" required
                        autocomplete="current-password" placeholder="Digite sua senha"
                        class="h-12 w-full rounded-xl border bg-white py-3 pl-4 pr-11 text-sm text-slate-950 outline-none transition placeholder:text-slate-400 focus:ring-2 {{ $errors->has('password') ? 'border-red-400 focus:border-red-500 focus:ring-red-100' : 'border-slate-300 focus:border-violet-500 focus:ring-violet-200' }}">
                    <button type="button"
                        class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-slate-400 transition hover:text-violet-600"
                        x-on:click="showPassword = ! showPassword"
                        x-bind:aria-label="showPassword ? 'Ocultar senha' : 'Mostrar senha'">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" />
                            <circle cx="12" cy="12" r="2.5" stroke-width="1.8" />
                        </svg>
                    </button>
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="mt-5">
                <label for="remember_me" class="inline-flex cursor-pointer items-center">
                    <input id="remember_me" type="checkbox"
                        class="rounded border-slate-300 text-violet-600 shadow-sm focus:ring-violet-500"
                        name="remember">
                    <span class="ml-2 text-sm text-slate-600">Lembrar de mim</span>
                </label>
            </div>

            <button type="submit" x-bind:disabled="submitting"
                class="mt-7 inline-flex min-h-12 w-full items-center justify-center rounded-full bg-violet-700 px-7 py-3 text-sm font-semibold text-white transition hover:bg-violet-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-600 focus-visible:ring-offset-2 disabled:cursor-wait disabled:bg-violet-400"
                x-text="submitting ? 'Entrando...' : 'Entrar'">
                Entrar
            </button>

            <p class="mt-5 text-center text-sm text-slate-600">
                Ainda não possui conta?
                <a href="{{ route('register') }}"
                    class="font-semibold text-violet-700 hover:text-violet-800 focus:outline-none focus:underline">
                    Criar conta
                </a>
            </p>
        </form>
    </div>
</x-guest-layout>
