<x-guest-layout title="Criar conta">
    <div class="mb-8 text-center">
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">
            <span class="text-violet-600">Infinit</span> ERP
        </h1>
        <p class="mt-2 text-sm text-slate-500">Crie sua conta e empresa</p>
    </div>

    <form
        method="POST"
        action="{{ route('register') }}"
        x-data="registrationForm({{ Js::from([
            'step' => $errors->hasAny(['business_trade_name', 'business_legal_name', 'business_document', 'business_phone', 'business_email']) ? 2 : 1,
            'businessDocument' => old('business_document', ''),
            'businessPhone' => old('business_phone', ''),
        ]) }})"
        @submit="submitForm($event)"
        class="space-y-5"
    >
        @csrf

        <div class="mb-2 flex items-center gap-3" aria-label="Etapas do cadastro">
            <div class="h-2 flex-1 rounded-full" :class="step >= 1 ? 'bg-violet-600' : 'bg-slate-200'"></div>
            <div class="h-2 flex-1 rounded-full" :class="step >= 2 ? 'bg-violet-600' : 'bg-slate-200'"></div>
        </div>

        <section x-show="step === 1" x-ref="userStep" class="space-y-5">
            <div>
                <h2 class="text-lg font-semibold text-slate-800">Dados do usuário</h2>
                <p class="mt-1 text-sm text-slate-500">Informe os dados que serão usados para acessar o sistema.</p>
            </div>

            <div>
                <label for="name" class="mb-2 block text-sm font-medium text-slate-700">Nome completo</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="Seu nome completo" class="block w-full rounded-lg border-slate-300 px-3.5 py-2.5 text-sm shadow-sm placeholder:text-slate-400 focus:border-violet-500 focus:ring-violet-500">
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <label for="email" class="mb-2 block text-sm font-medium text-slate-700">E-mail</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" placeholder="seu@email.com" class="block w-full rounded-lg border-slate-300 px-3.5 py-2.5 text-sm shadow-sm placeholder:text-slate-400 focus:border-violet-500 focus:ring-violet-500">
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <label for="password" class="mb-2 block text-sm font-medium text-slate-700">Senha</label>
                <div class="relative">
                    <input id="password" :type="showPassword ? 'text' : 'password'" name="password" required autocomplete="new-password" placeholder="Mínimo de 8 caracteres" class="block w-full rounded-lg border-slate-300 py-2.5 pl-3.5 pr-11 text-sm shadow-sm placeholder:text-slate-400 focus:border-violet-500 focus:ring-violet-500">
                    <button type="button" class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-slate-400 hover:text-violet-600" @click="showPassword = ! showPassword" :aria-label="showPassword ? 'Ocultar senha' : 'Mostrar senha'">
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
                    <input id="password_confirmation" :type="showPasswordConfirmation ? 'text' : 'password'" name="password_confirmation" required autocomplete="new-password" placeholder="Repita sua senha" class="block w-full rounded-lg border-slate-300 py-2.5 pl-3.5 pr-11 text-sm shadow-sm placeholder:text-slate-400 focus:border-violet-500 focus:ring-violet-500">
                    <button type="button" class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-slate-400 hover:text-violet-600" @click="showPasswordConfirmation = ! showPasswordConfirmation" :aria-label="showPasswordConfirmation ? 'Ocultar confirmação da senha' : 'Mostrar confirmação da senha'">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" />
                            <circle cx="12" cy="12" r="2.5" stroke-width="1.8" />
                        </svg>
                    </button>
                </div>
            </div>

            <button type="button" class="flex w-full justify-center rounded-lg bg-gradient-to-r from-violet-600 to-violet-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-violet-200 transition hover:from-violet-700 hover:to-violet-800 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2" @click="if ([...$refs.userStep.querySelectorAll('input')].every(input => input.reportValidity())) step = 2">
                Continuar
            </button>
        </section>

        <section x-show="step === 2" x-cloak class="space-y-5">
            <div>
                <h2 class="text-lg font-semibold text-slate-800">Dados da empresa</h2>
                <p class="mt-1 text-sm text-slate-500">Esta empresa terá seus dados isolados das demais.</p>
            </div>

            <div>
                <label for="business_trade_name" class="mb-2 block text-sm font-medium text-slate-700">Nome fantasia</label>
                <input id="business_trade_name" type="text" name="business_trade_name" value="{{ old('business_trade_name') }}" required autocomplete="organization" class="block w-full rounded-lg border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500">
                <x-input-error :messages="$errors->get('business_trade_name')" class="mt-2" />
            </div>

            <div>
                <label for="business_legal_name" class="mb-2 block text-sm font-medium text-slate-700">Razão social</label>
                <input id="business_legal_name" type="text" name="business_legal_name" value="{{ old('business_legal_name') }}" required class="block w-full rounded-lg border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500">
                <x-input-error :messages="$errors->get('business_legal_name')" class="mt-2" />
            </div>

            <div>
                <label for="business_document" class="mb-2 block text-sm font-medium text-slate-700">CPF/CNPJ</label>
                <input id="business_document" x-ref="businessDocument" x-model="businessDocument" @input="maskDocument($event)" @blur="validateDocument" type="text" name="business_document" required maxlength="18" inputmode="text" autocapitalize="characters" spellcheck="false" x-bind:aria-invalid="documentError !== ''" x-bind:class="documentError ? 'border-rose-300 focus:border-rose-400' : 'border-slate-300 focus:border-violet-500'" class="block w-full rounded-lg px-3.5 py-2.5 text-sm shadow-sm focus:ring-violet-500">
                <p x-cloak x-show="documentError" x-text="documentError" class="mt-2 text-sm text-rose-600"></p>
                <x-input-error :messages="$errors->get('business_document')" x-show="! documentError" class="mt-2" />
            </div>

            <div>
                <label for="business_phone" class="mb-2 block text-sm font-medium text-slate-700">Telefone</label>
                <input id="business_phone" x-model="businessPhone" @input="maskPhone($event)" type="tel" name="business_phone" required maxlength="15" autocomplete="tel" inputmode="tel" class="block w-full rounded-lg border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500">
                <x-input-error :messages="$errors->get('business_phone')" class="mt-2" />
            </div>

            <div>
                <label for="business_email" class="mb-2 block text-sm font-medium text-slate-700">E-mail da empresa</label>
                <input id="business_email" type="email" name="business_email" value="{{ old('business_email') }}" required autocomplete="email" class="block w-full rounded-lg border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500">
                <x-input-error :messages="$errors->get('business_email')" class="mt-2" />
            </div>

            <div class="flex gap-3">
                <button type="button" class="flex w-full justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50" @click="step = 1">
                    Voltar
                </button>
                <button type="submit" class="flex w-full justify-center rounded-lg bg-gradient-to-r from-violet-600 to-violet-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-violet-200 transition hover:from-violet-700 hover:to-violet-800 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2">
                    Criar conta
                </button>
            </div>
        </section>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
        Já possui uma conta?
        <a href="{{ route('login') }}" class="font-semibold text-violet-600 hover:text-violet-700 focus:outline-none focus:underline">Entrar</a>
    </p>
</x-guest-layout>
