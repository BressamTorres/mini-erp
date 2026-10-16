<x-app-layout>
    <x-slot name="title">Dados da Empresa | Infinit ERP</x-slot>

    <div class="mx-auto max-w-5xl space-y-6">
        <header>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Dados da Empresa</h1>
            <p class="mt-1 text-sm text-slate-500">Mantenha os dados cadastrais da sua empresa atualizados.</p>
        </header>

        @if (session('success'))
            <div class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m5 12 4 4L19 6" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('settings.business.update') }}"
            x-data="businessSettings({{ Js::from([
                'document' => old('document', $business->document),
                'phone' => old('phone', $business->phone),
            ]) }})"
            @submit="submit($event)"
            class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
        >
            @csrf
            @method('PUT')

            <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                <h2 class="font-semibold text-slate-900">Informações cadastrais</h2>
                <p class="mt-1 text-sm text-slate-500">Esses dados identificam sua empresa no Mini ERP.</p>
            </div>

            <div class="grid grid-cols-1 gap-5 p-5 sm:grid-cols-2 sm:p-6">
                <div>
                    <label for="trade_name" class="mb-2 block text-sm font-medium text-slate-700">Nome fantasia</label>
                    <input id="trade_name" name="trade_name" type="text" value="{{ old('trade_name', $business->trade_name) }}" required maxlength="255" autocomplete="organization" @class([
                        'block w-full rounded-lg px-3.5 py-2.5 text-sm shadow-sm focus:ring-blue-500',
                        'border-rose-300 focus:border-rose-400' => $errors->has('trade_name'),
                        'border-slate-300 focus:border-blue-500' => ! $errors->has('trade_name'),
                    ])>
                    <x-input-error :messages="$errors->get('trade_name')" class="mt-2" />
                </div>

                <div>
                    <label for="legal_name" class="mb-2 block text-sm font-medium text-slate-700">Razão social</label>
                    <input id="legal_name" name="legal_name" type="text" value="{{ old('legal_name', $business->legal_name) }}" required maxlength="255" @class([
                        'block w-full rounded-lg px-3.5 py-2.5 text-sm shadow-sm focus:ring-blue-500',
                        'border-rose-300 focus:border-rose-400' => $errors->has('legal_name'),
                        'border-slate-300 focus:border-blue-500' => ! $errors->has('legal_name'),
                    ])>
                    <x-input-error :messages="$errors->get('legal_name')" class="mt-2" />
                </div>

                <div>
                    <label for="document" class="mb-2 block text-sm font-medium text-slate-700">CPF/CNPJ</label>
                    <input id="document" x-ref="document" x-model="document" @input="maskDocument($event)" @blur="validateDocument" name="document" type="text" required maxlength="18" inputmode="text" autocapitalize="characters" spellcheck="false" x-bind:aria-invalid="documentError !== ''" x-bind:class="documentError ? 'border-rose-300 focus:border-rose-400' : 'border-slate-300 focus:border-blue-500'" class="block w-full rounded-lg px-3.5 py-2.5 text-sm shadow-sm focus:ring-blue-500">
                    <p x-cloak x-show="documentError" x-text="documentError" class="mt-2 text-sm text-rose-600" role="alert"></p>
                    <x-input-error :messages="$errors->get('document')" x-show="! documentError" class="mt-2" />
                </div>

                <div>
                    <label for="phone" class="mb-2 block text-sm font-medium text-slate-700">Telefone</label>
                    <input id="phone" x-model="phone" @input="maskPhone($event)" name="phone" type="tel" required maxlength="15" autocomplete="tel" inputmode="tel" @class([
                        'block w-full rounded-lg px-3.5 py-2.5 text-sm shadow-sm focus:ring-blue-500',
                        'border-rose-300 focus:border-rose-400' => $errors->has('phone'),
                        'border-slate-300 focus:border-blue-500' => ! $errors->has('phone'),
                    ])>
                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <label for="email" class="mb-2 block text-sm font-medium text-slate-700">E-mail da empresa</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $business->email) }}" required maxlength="255" autocomplete="email" @class([
                        'block w-full rounded-lg px-3.5 py-2.5 text-sm shadow-sm focus:ring-blue-500',
                        'border-rose-300 focus:border-rose-400' => $errors->has('email'),
                        'border-slate-300 focus:border-blue-500' => ! $errors->has('email'),
                    ])>
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>
            </div>

            <div class="flex justify-end border-t border-slate-100 bg-slate-50/70 px-5 py-4 sm:px-6">
                <button type="submit" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-blue-200 transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 4h11l3 3v13H5V4Zm3 0v6h8V4M8 20v-6h8v6" />
                    </svg>
                    Salvar alterações
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
