<form method="POST" action="{{ route('customers.store') }}" enctype="multipart/form-data" x-bind:action="formAction">
    @csrf
    <input type="hidden" name="_method" value="PUT" disabled x-bind:disabled="mode !== 'edit'">
    <input type="hidden" name="form_mode" x-bind:value="mode">
    <input type="hidden" name="customer_id" x-bind:value="customerId">
    <input type="hidden" name="remove_photo" x-bind:value="shouldRemovePhoto ? '1' : '0'">

    <div class="grid gap-6 px-5 py-6 sm:px-6 md:grid-cols-[minmax(0,1fr)_13rem]">
        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="customer-name" class="mb-2 block text-sm font-medium text-slate-700">
                    Nome <span class="text-rose-500">*</span>
                </label>
                <input
                    id="customer-name"
                    name="name"
                    type="text"
                    required
                    maxlength="255"
                    autocomplete="name"
                    x-ref="customerName"
                    x-model="fields.name"
                    @class([
                        'block w-full rounded-lg px-3.5 py-2.5 text-sm shadow-sm placeholder:text-slate-400 focus:ring-violet-500',
                        'border-rose-300 focus:border-rose-400' => $errors->has('name'),
                        'border-slate-300 focus:border-violet-500' => ! $errors->has('name'),
                    ])
                    placeholder="Nome do cliente"
                >
                @error('name')
                    <p x-show="showServerErrors" class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="customer-document" class="mb-2 block text-sm font-medium text-slate-700">CPF/CNPJ</label>
                <input
                    id="customer-document"
                    name="document"
                    type="text"
                    maxlength="18"
                    inputmode="numeric"
                    x-bind:value="fields.document"
                    @input="maskDocument($event)"
                    @class([
                        'block w-full rounded-lg px-3.5 py-2.5 text-sm shadow-sm placeholder:text-slate-400 focus:ring-violet-500',
                        'border-rose-300 focus:border-rose-400' => $errors->has('document'),
                        'border-slate-300 focus:border-violet-500' => ! $errors->has('document'),
                    ])
                    placeholder="000.000.000-00"
                >
                @error('document')
                    <p x-show="showServerErrors" class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="customer-phone" class="mb-2 block text-sm font-medium text-slate-700">
                    Telefone <span class="text-rose-500">*</span>
                </label>
                <input
                    id="customer-phone"
                    name="phone"
                    type="tel"
                    required
                    maxlength="15"
                    autocomplete="tel"
                    inputmode="tel"
                    x-bind:value="fields.phone"
                    @input="maskPhone($event)"
                    @class([
                        'block w-full rounded-lg px-3.5 py-2.5 text-sm shadow-sm placeholder:text-slate-400 focus:ring-violet-500',
                        'border-rose-300 focus:border-rose-400' => $errors->has('phone'),
                        'border-slate-300 focus:border-violet-500' => ! $errors->has('phone'),
                    ])
                    placeholder="(00) 00000-0000"
                >
                @error('phone')
                    <p x-show="showServerErrors" class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-2">
                <label for="customer-email" class="mb-2 block text-sm font-medium text-slate-700">E-mail</label>
                <input
                    id="customer-email"
                    name="email"
                    type="email"
                    maxlength="255"
                    autocomplete="email"
                    x-model="fields.email"
                    @class([
                        'block w-full rounded-lg px-3.5 py-2.5 text-sm shadow-sm placeholder:text-slate-400 focus:ring-violet-500',
                        'border-rose-300 focus:border-rose-400' => $errors->has('email'),
                        'border-slate-300 focus:border-violet-500' => ! $errors->has('email'),
                    ])
                    placeholder="cliente@email.com"
                >
                @error('email')
                    <p x-show="showServerErrors" class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <input
                id="customer-photo"
                name="photo"
                type="file"
                class="sr-only"
                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                x-ref="customerPhoto"
                @change="selectPhoto($event)"
            >

            <div
                @class([
                    'flex min-h-64 flex-col items-center justify-center rounded-xl border bg-slate-50/70 p-4 text-center',
                    'border-rose-300' => $errors->has('photo'),
                    'border-slate-200' => ! $errors->has('photo'),
                ])
            >
                <div class="flex h-32 w-full items-center justify-center overflow-hidden rounded-lg bg-white">
                    <img
                        x-show="photoPreview"
                        x-bind:src="photoPreview"
                        class="h-full w-full object-cover"
                        alt="Pré-visualização da foto do cliente"
                    >

                    <svg x-show="! photoPreview" class="h-10 w-10 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M3 16.5 8.5 11l4 4L15 12.5l6 6M5 20h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Zm11-11h.01" />
                    </svg>
                </div>

                <p class="mt-3 text-sm font-semibold text-slate-700">Foto do cliente</p>
                <p class="mt-1 text-xs text-slate-500">PNG, JPG ou WEBP</p>
                <p class="text-xs text-slate-500">Máx. 2 MB</p>

                <button
                    type="button"
                    class="mt-4 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-violet-300 hover:text-violet-700 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-1"
                    @click="$refs.customerPhoto.click()"
                >
                    Selecionar imagem
                </button>

                <button
                    x-show="photoPreview"
                    type="button"
                    class="mt-2 text-xs font-medium text-rose-600 transition hover:text-rose-700"
                    @click="removePhoto"
                >
                    Remover foto
                </button>
            </div>

            @error('photo')
                <p x-show="showServerErrors" class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="flex flex-col-reverse gap-3 border-t border-slate-200 bg-slate-50/70 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
        <button type="button" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50" @click="closeModal">
            Cancelar
        </button>
        <button type="submit" class="rounded-lg bg-gradient-to-r from-violet-600 to-violet-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-violet-200 transition hover:from-violet-700 hover:to-violet-800 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2" x-text="mode === 'edit' ? 'Salvar alterações' : 'Salvar cliente'">
            Salvar cliente
        </button>
    </div>
</form>
