<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCatalogSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'external_order_id' => ['required', 'uuid'],
            'customer_id' => ['nullable', 'integer'],
            'seller_id' => ['nullable', 'integer'],
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['required', 'array'],
            'items.*.product_id' => ['required', 'integer', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array{
     *     external_order_id: string,
     *     customer_id: int|null,
     *     seller_id: int|null,
     *     items: array<int, array{product_id: int, quantity: int}>
     * }
     */
    public function payload(): array
    {
        $validated = $this->validated();

        return [
            'external_order_id' => $validated['external_order_id'],
            'customer_id' => isset($validated['customer_id']) ? (int) $validated['customer_id'] : null,
            'seller_id' => isset($validated['seller_id']) ? (int) $validated['seller_id'] : null,
            'items' => array_map(fn (array $item): array => [
                'product_id' => (int) $item['product_id'],
                'quantity' => (int) $item['quantity'],
            ], $validated['items']),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'external_order_id.required' => 'O identificador externo do pedido é obrigatório.',
            'external_order_id.uuid' => 'O identificador externo do pedido deve ser um UUID válido.',
            'customer_id.integer' => 'O cliente informado é inválido.',
            'seller_id.integer' => 'O vendedor informado é inválido.',
            'items.required' => 'Adicione pelo menos um item à venda.',
            'items.array' => 'Os itens da venda são inválidos.',
            'items.min' => 'Adicione pelo menos um item à venda.',
            'items.*.array' => 'O item da venda é inválido.',
            'items.*.product_id.required' => 'Selecione um produto.',
            'items.*.product_id.integer' => 'O produto informado é inválido.',
            'items.*.product_id.distinct' => 'O mesmo produto não pode ser adicionado mais de uma vez.',
            'items.*.quantity.required' => 'Informe a quantidade do produto.',
            'items.*.quantity.integer' => 'A quantidade deve ser um número inteiro.',
            'items.*.quantity.min' => 'A quantidade deve ser pelo menos 1.',
        ];
    }
}
