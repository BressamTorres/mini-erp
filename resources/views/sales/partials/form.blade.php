<form method="POST" action="{{ route('sales.store') }}" x-bind:action="formAction" @submit="guardSubmit($event)">
    @csrf
    <input type="hidden" name="_method" value="PUT" x-bind:disabled="mode !== 'edit'">
    <input type="hidden" name="form_mode" x-bind:value="mode">
    <input type="hidden" name="sale_id" x-bind:value="saleId">

    <div class="space-y-5 px-5 py-5 sm:px-6">
        <div x-show="formLoading" class="flex min-h-64 items-center justify-center text-sm text-slate-500">Carregando venda...</div>
        <div x-show="formError" class="flex min-h-64 items-center justify-center text-sm font-medium text-rose-600" x-text="formError"></div>

        <div x-show="! formLoading && ! formError" class="space-y-5">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="sale-customer" class="mb-2 block text-sm font-medium text-slate-700">Cliente <span class="text-rose-500">*</span></label>
                    <x-form-combobox id="sale-customer" name="customer_id" model="fields.customer_id" options="customerComboboxOptions" placeholder="Selecione o cliente" :error="$errors->has('customer_id')" />
                    @error('customer_id')<p x-show="showServerErrors" class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="sale-seller" class="mb-2 block text-sm font-medium text-slate-700">Vendedor <span class="text-rose-500">*</span></label>
                    <x-form-combobox id="sale-seller" name="seller_id" model="fields.seller_id" options="sellerComboboxOptions" placeholder="Selecione o vendedor" :error="$errors->has('seller_id')" />
                    @error('seller_id')<p x-show="showServerErrors" class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div x-cloak x-show="mode === 'edit'">
                <label for="sale-active" class="mb-2 block text-sm font-medium text-slate-700">Situação <span class="text-rose-500">*</span></label>
                <x-form-combobox id="sale-active" name="active" model="fields.active" options="statusComboboxOptions" placeholder="Selecione a situação" disabled="mode !== 'edit'" :error="$errors->has('active')" />
                @error('active')<p x-show="showServerErrors" class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <p class="mb-2 text-sm font-medium text-slate-700">Itens da venda <span class="text-rose-500">*</span></p>
                <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_7rem_auto]">
                    <div>
                        <label for="sale-product" class="sr-only">Produto</label>
                        <x-form-combobox id="sale-product" name="selected_product_id" model="selectedProductId" options="productComboboxOptions" placeholder="Selecione o produto" />
                    </div>
                    <div>
                        <label for="sale-new-quantity" class="sr-only">Quantidade</label>
                        <input id="sale-new-quantity" type="number" min="1" step="1" x-model.number="newQuantity" class="block w-full rounded-lg border-slate-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500" placeholder="Qtd.">
                    </div>
                    <button type="button" class="rounded-lg bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700 disabled:cursor-not-allowed disabled:opacity-50" :disabled="! selectedProductId" @click="addItem">Adicionar</button>
                </div>
                @if ($errors->has('items') || $errors->has('items.*'))
                    <p x-show="showServerErrors" class="mt-1.5 text-sm text-rose-600">{{ $errors->first('items') ?: $errors->first('items.*') }}</p>
                @endif
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50"><tr>
                        <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Imagem</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Código</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Produto</th>
                        <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">Quant.</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Valor</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Subtotal</th>
                        <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">Ações</th>
                    </tr></thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <template x-for="(item, index) in items" :key="item.saleItemId ? `existing-${item.saleItemId}` : `new-${item.productId}`">
                            <tr>
                                <td class="px-3 py-2.5"><div class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-lg bg-slate-100"><img x-show="item.photoUrl" :src="item.photoUrl" class="h-full w-full object-cover" alt=""><svg x-show="! item.photoUrl" class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M3 16.5 8.5 11l4 4L15 12.5l6 6M5 20h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z" /></svg></div></td>
                                <td class="whitespace-nowrap px-3 py-2.5 text-sm text-slate-600" x-text="item.sku"></td>
                                <td class="px-3 py-2.5 text-sm font-medium text-slate-800" x-text="item.name"></td>
                                <td class="px-3 py-2.5 text-center">
                                    <input type="hidden" :name="`items[${index}][sale_item_id]`" :value="item.saleItemId" :disabled="! item.saleItemId">
                                    <input type="hidden" :name="`items[${index}][product_id]`" :value="item.productId">
                                    <input type="number" min="1" step="1" :name="`items[${index}][quantity]`" x-model.number="item.quantity" @change="normalizeQuantity(item)" class="w-20 rounded-lg border-slate-300 px-2 py-1.5 text-center text-sm focus:border-violet-500 focus:ring-violet-500">
                                </td>
                                <td class="whitespace-nowrap px-3 py-2.5 text-right text-sm text-slate-600" x-text="money(item.unitPriceCents)"></td>
                                <td class="whitespace-nowrap px-3 py-2.5 text-right text-sm font-semibold text-slate-800" x-text="itemSubtotal(item)"></td>
                                <td class="px-3 py-2.5 text-center">
                                    <x-grid-action-button icon="trash-2" variant="deactivate" title="Remover" @click="removeItem(index)" aria-label="Remover item" />
                                </td>
                            </tr>
                        </template>
                        <tr x-show="items.length === 0"><td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500">Nenhum item adicionado.</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-end gap-4">
                <span class="text-sm font-semibold text-slate-600">Total</span>
                <strong class="text-xl text-slate-900" x-text="money(visualTotal)"></strong>
            </div>
        </div>
    </div>

    <div class="flex flex-col-reverse gap-3 border-t border-slate-200 bg-slate-50/70 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
        <button type="button" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50" @click="closeForm">Cancelar</button>
        <button type="submit" :disabled="! canSubmit" class="rounded-lg bg-gradient-to-r from-violet-600 to-violet-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-violet-200 transition hover:from-violet-700 hover:to-violet-800 disabled:cursor-not-allowed disabled:opacity-50" x-text="submitting ? 'Salvando...' : (mode === 'edit' ? 'Salvar alterações' : 'Registrar venda')">Registrar venda</button>
    </div>
</form>
