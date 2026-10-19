<?php

namespace App\Http\Requests;

use App\Rules\Cpf;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCatalogSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $customer = $this->input('customer');

        if (! is_array($customer)) {
            return;
        }

        foreach (['name', 'email'] as $field) {
            if (isset($customer[$field]) && is_string($customer[$field])) {
                $customer[$field] = trim($customer[$field]);
            }
        }

        foreach (['document', 'phone'] as $field) {
            if (isset($customer[$field]) && is_string($customer[$field])) {
                $customer[$field] = preg_replace('/\D+/', '', $customer[$field]);
            }
        }

        $this->merge(['customer' => $customer]);
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
            'customer_id' => ['nullable', 'integer', 'prohibits:customer'],
            'customer' => [
                'nullable',
                'array:name,document,phone,email',
                'required_array_keys:name,document,phone,email',
                'prohibits:customer_id',
            ],
            'customer.name' => ['required_with:customer', 'string', 'max:255'],
            'customer.document' => ['bail', 'required_with:customer', 'string', 'regex:/^\d{11}$/', new Cpf],
            'customer.phone' => ['required_with:customer', 'string', 'regex:/^\d{10,11}$/'],
            'customer.email' => ['required_with:customer', 'email', 'max:255'],
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
     *     customer: array{name: string, document: string, phone: string, email: string}|null,
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
            'customer' => isset($validated['customer']) ? [
                'name' => $validated['customer']['name'],
                'document' => $validated['customer']['document'],
                'phone' => $validated['customer']['phone'],
                'email' => $validated['customer']['email'],
            ] : null,
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
            'customer_id.prohibits' => 'Informe o cliente pelo ID ou pelos dados cadastrais, não pelos dois formatos.',
            'customer.array' => 'Os dados do cliente são inválidos.',
            'customer.required_array_keys' => 'Informe nome, CPF, telefone e e-mail do cliente.',
            'customer.prohibits' => 'Informe o cliente pelo ID ou pelos dados cadastrais, não pelos dois formatos.',
            'customer.name.required_with' => 'O nome do cliente é obrigatório.',
            'customer.name.string' => 'O nome do cliente deve ser um texto.',
            'customer.name.max' => 'O nome do cliente deve ter no máximo 255 caracteres.',
            'customer.document.required_with' => 'O CPF do cliente é obrigatório.',
            'customer.document.string' => 'O CPF do cliente deve ser um texto.',
            'customer.document.regex' => 'O CPF do cliente deve conter 11 dígitos.',
            'customer.phone.required_with' => 'O telefone do cliente é obrigatório.',
            'customer.phone.string' => 'O telefone do cliente deve ser um texto.',
            'customer.phone.regex' => 'O telefone do cliente deve conter 10 ou 11 dígitos.',
            'customer.email.required_with' => 'O e-mail do cliente é obrigatório.',
            'customer.email.email' => 'Informe um e-mail válido para o cliente.',
            'customer.email.max' => 'O e-mail do cliente deve ter no máximo 255 caracteres.',
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
