<?php

namespace App\Http\Requests;

use App\Models\Person;
use App\Services\BusinessContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->exists('active')) {
            $active = $this->input('active');
            $this->merge([
                'active' => match ($active) {
                    '1', 1 => true,
                    '0', 0 => false,
                    default => $active,
                },
            ]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('persons', 'id')->where(fn ($query) => $query
                    ->where('business_id', app(BusinessContext::class)->id())
                    ->where('type', Person::TYPE_CUSTOMER)
                    ->where('active', true)),
            ],
            'seller_id' => [
                'required',
                'integer',
                Rule::exists('persons', 'id')->where(fn ($query) => $query
                    ->where('business_id', app(BusinessContext::class)->id())
                    ->where('type', Person::TYPE_SELLER)
                    ->where('active', true)),
            ],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('products', 'id')->where(fn ($query) => $query
                    ->where('business_id', app(BusinessContext::class)->id())
                    ->where('active', true)),
            ],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'submission_token' => ['nullable', 'uuid'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_id.required' => 'O cliente é obrigatório.',
            'customer_id.exists' => 'Selecione um cliente ativo válido.',
            'seller_id.required' => 'O vendedor é obrigatório.',
            'seller_id.exists' => 'Selecione um vendedor ativo válido.',
            'items.required' => 'Adicione pelo menos um item à venda.',
            'items.array' => 'Os itens da venda são inválidos.',
            'items.min' => 'Adicione pelo menos um item à venda.',
            'items.*.product_id.required' => 'Selecione um produto.',
            'items.*.product_id.exists' => 'Selecione um produto ativo válido.',
            'items.*.product_id.distinct' => 'O mesmo produto não pode ser adicionado mais de uma vez.',
            'items.*.quantity.required' => 'Informe a quantidade do produto.',
            'items.*.quantity.integer' => 'A quantidade deve ser um número inteiro.',
            'items.*.quantity.min' => 'A quantidade deve ser pelo menos 1.',
            'submission_token.uuid' => 'Não foi possível identificar este envio. Tente novamente.',
        ];
    }
}
