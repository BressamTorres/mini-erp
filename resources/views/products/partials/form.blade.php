<form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data" x-bind:action="formAction" @submit="guardSubmit($event)">
    @csrf
    <input type="hidden" name="_method" value="PUT" disabled x-bind:disabled="mode !== 'edit'">
    <input type="hidden" name="form_mode" x-bind:value="mode">
    <input type="hidden" name="product_id" x-bind:value="productId">
    <input type="hidden" name="remove_photo" x-bind:value="shouldRemovePhoto ? '1' : '0'">
    <input type="hidden" name="submission_token" x-bind:value="submissionToken">

    <div class="grid gap-6 px-5 py-6 sm:px-6 md:grid-cols-[minmax(0,1fr)_13rem]">
        <div class="grid content-start gap-5">
            <div>
                <label for="product-sku" class="mb-2 block text-sm font-medium text-slate-700">Código (SKU) <span class="text-rose-500">*</span></label>
                <input id="product-sku" name="sku" type="text" required maxlength="100" x-ref="productSku" x-model="fields.sku"
                    @class(['block w-full rounded-lg px-3.5 py-2.5 text-sm shadow-sm placeholder:text-slate-400 focus:ring-violet-500', 'border-rose-300 focus:border-rose-400' => $errors->has('sku'), 'border-slate-300 focus:border-violet-500' => ! $errors->has('sku')])
                    placeholder="Ex.: CAM001">
                @error('sku')<p x-show="showServerErrors" class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="product-name" class="mb-2 block text-sm font-medium text-slate-700">Nome <span class="text-rose-500">*</span></label>
                <input id="product-name" name="name" type="text" required maxlength="255" x-model="fields.name"
                    @class(['block w-full rounded-lg px-3.5 py-2.5 text-sm shadow-sm placeholder:text-slate-400 focus:ring-violet-500', 'border-rose-300 focus:border-rose-400' => $errors->has('name'), 'border-slate-300 focus:border-violet-500' => ! $errors->has('name')])
                    placeholder="Nome do produto">
                @error('name')<p x-show="showServerErrors" class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="product-sale-price" class="mb-2 block text-sm font-medium text-slate-700">Preço de venda <span class="text-rose-500">*</span></label>
                <input id="product-sale-price" name="sale_price" type="text" required maxlength="22" inputmode="decimal" x-bind:value="fields.sale_price" @input="maskSalePrice($event)"
                    @class(['block w-full rounded-lg px-3.5 py-2.5 text-sm shadow-sm placeholder:text-slate-400 focus:ring-violet-500', 'border-rose-300 focus:border-rose-400' => $errors->has('sale_price'), 'border-slate-300 focus:border-violet-500' => ! $errors->has('sale_price')])
                    placeholder="R$ 0,00">
                @error('sale_price')<p x-show="showServerErrors" class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div x-cloak x-show="mode === 'edit'">
                <label for="product-active" class="mb-2 block text-sm font-medium text-slate-700">Situação <span class="text-rose-500">*</span></label>
                <x-form-combobox id="product-active" name="active" model="fields.active" options="[{ value: '1', label: 'Ativo' }, { value: '0', label: 'Inativo' }]" placeholder="Selecione a situação" disabled="mode !== 'edit'" :error="$errors->has('active')" />
                @error('active')<p x-show="showServerErrors" class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <input id="product-photo" name="photo" type="file" class="sr-only" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" x-ref="productPhoto" @change="selectPhoto($event)">

            <div @class(['flex min-h-64 flex-col items-center justify-center rounded-xl border bg-slate-50/70 p-4 text-center', 'border-rose-300' => $errors->has('photo'), 'border-slate-200' => ! $errors->has('photo')])>
                <div class="flex h-32 w-full items-center justify-center overflow-hidden rounded-lg bg-white">
                    <img x-show="photoPreview" x-bind:src="photoPreview" class="h-full w-full object-cover" alt="Pré-visualização da foto do produto">
                    <svg x-show="! photoPreview" class="h-10 w-10 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M3 16.5 8.5 11l4 4L15 12.5l6 6M5 20h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Zm11-11h.01" />
                    </svg>
                </div>
                <p class="mt-3 text-sm font-semibold text-slate-700">Imagem do produto</p>
                <p class="mt-1 text-xs text-slate-500">PNG, JPG ou WEBP</p>
                <p class="text-xs text-slate-500">Máx. 5 MB</p>
                <button type="button" class="mt-4 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-violet-300 hover:text-violet-700 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-1" @click="$refs.productPhoto.click()">Selecionar imagem</button>
                <button x-show="photoPreview" type="button" class="mt-2 text-xs font-medium text-rose-600 transition hover:text-rose-700" @click="removePhoto">Remover foto</button>
            </div>
            @error('photo')<p x-show="showServerErrors" class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="flex flex-col-reverse gap-3 border-t border-slate-200 bg-slate-50/70 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
        <button type="button" :disabled="submitting" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 disabled:cursor-wait disabled:opacity-60" @click="closeModal">Cancelar</button>
        <button type="submit" :disabled="submitting" class="inline-flex items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-violet-600 to-violet-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-violet-200 transition hover:from-violet-700 hover:to-violet-800 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-70">
            <svg x-cloak x-show="submitting" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" /><path class="opacity-90" fill="currentColor" d="M21 12a9 9 0 0 0-9-9v3a6 6 0 0 1 6 6h3Z" /></svg>
            <span x-text="submitting ? 'Salvando...' : (mode === 'edit' ? 'Salvar alterações' : 'Salvar produto')">Salvar produto</span>
        </button>
    </div>
</form>
