<?php

namespace App\Http\Requests;

use App\Models\Person;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSaleRequest extends StoreSaleRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $sale = Sale::query()->with('items:id,sale_id,product_id')->find($this->route('sale'));

        return [
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('persons', 'id')->where(fn ($query) => $query
                    ->where('type', Person::TYPE_CUSTOMER)
                    ->where(fn ($query) => $query
                        ->where('active', true)
                        ->when($sale !== null, fn ($query) => $query->orWhere('id', $sale->customer_id)))),
            ],
            'seller_id' => [
                'required',
                'integer',
                Rule::exists('persons', 'id')->where(fn ($query) => $query
                    ->where('type', Person::TYPE_SELLER)
                    ->where(fn ($query) => $query
                        ->where('active', true)
                        ->when($sale !== null, fn ($query) => $query->orWhere('id', $sale->seller_id)))),
            ],
            'active' => ['required', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sale_item_id' => [
                'nullable',
                'integer',
                'distinct',
                Rule::exists('sale_items', 'id')->where(
                    fn ($query) => $query->where('sale_id', $sale?->id ?? 0),
                ),
            ],
            'items.*.product_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('products', 'id'),
            ],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $sale = Sale::query()
                ->with('items:id,sale_id,product_id')
                ->find($this->route('sale'));
            $submittedItems = $this->input('items', []);

            if ($sale === null || ! is_array($submittedItems)) {
                return;
            }

            $historicalItems = $sale->items->keyBy('id');
            $newProductIds = collect($submittedItems)
                ->filter(fn ($item): bool => is_array($item) && empty($item['sale_item_id']) && is_numeric($item['product_id'] ?? null))
                ->pluck('product_id')
                ->map(fn ($id): int => (int) $id)
                ->all();
            $activeProductIds = Product::query()
                ->whereIn('id', $newProductIds)
                ->where('active', true)
                ->pluck('id')
                ->all();

            foreach ($submittedItems as $index => $item) {
                if (! is_array($item) || ! is_numeric($item['product_id'] ?? null)) {
                    continue;
                }

                $productId = (int) $item['product_id'];
                $saleItemId = ! empty($item['sale_item_id']) && is_numeric($item['sale_item_id'])
                    ? (int) $item['sale_item_id']
                    : null;

                if ($saleItemId !== null) {
                    $historicalItem = $historicalItems->get($saleItemId);

                    if ($historicalItem !== null && $historicalItem->product_id !== $productId) {
                        $validator->errors()->add(
                            "items.{$index}.product_id",
                            'O produto não corresponde ao item histórico informado.',
                        );
                    }

                    continue;
                }

                if (! in_array($productId, $activeProductIds, true)) {
                    $validator->errors()->add(
                        "items.{$index}.product_id",
                        'Selecione um produto ativo válido.',
                    );
                }
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'active.required' => 'A situação é obrigatória.',
            'active.boolean' => 'Selecione uma situação válida.',
            'items.*.sale_item_id.integer' => 'O item histórico informado é inválido.',
            'items.*.sale_item_id.distinct' => 'O mesmo item histórico não pode ser informado mais de uma vez.',
            'items.*.sale_item_id.exists' => 'O item histórico não pertence a esta venda.',
        ];
    }
}
