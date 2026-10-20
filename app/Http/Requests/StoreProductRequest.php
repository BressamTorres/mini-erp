<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $normalized = [
            'sale_price' => $this->normalizeSalePrice($this->input('sale_price')),
        ];

        if ($this->exists('active')) {
            $active = $this->input('active');
            $normalized['active'] = match ($active) {
                '1', 1 => true,
                '0', 0 => false,
                default => $active,
            };
        }

        $this->merge($normalized);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sku' => ['prohibited'],
            'name' => ['required', 'string', 'max:255'],
            'sale_price' => ['required', 'numeric', 'gt:0'],
            'photo' => ['bail', 'nullable', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'image', 'dimensions:max_width=10000,max_height=10000'],
            'remove_photo' => ['nullable', 'boolean'],
            'submission_token' => ['nullable', 'uuid'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sku.prohibited' => 'O SKU é gerado automaticamente e não pode ser informado.',
            'name.required' => 'O nome é obrigatório.',
            'name.string' => 'O nome deve ser um texto.',
            'name.max' => 'O nome deve ter no máximo 255 caracteres.',
            'sale_price.required' => 'O preço de venda é obrigatório.',
            'sale_price.numeric' => 'Informe um preço de venda válido.',
            'sale_price.gt' => 'O preço de venda deve ser maior que zero.',
            'active.required' => 'A situação é obrigatória.',
            'active.boolean' => 'Informe uma situação válida.',
            'photo.mimes' => 'A foto deve estar no formato JPG, JPEG, PNG ou WEBP.',
            'photo.extensions' => 'A foto deve estar no formato JPG, JPEG, PNG ou WEBP.',
            'photo.image' => 'O arquivo enviado deve ser uma imagem válida.',
            'photo.dimensions' => 'A imagem possui dimensões muito grandes. Selecione uma imagem menor.',
            'photo.uploaded' => 'A imagem excede o limite técnico do servidor. Selecione um arquivo menor.',
            'submission_token.uuid' => 'Não foi possível identificar este envio. Tente novamente.',
        ];
    }

    private function normalizeSalePrice(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $normalized = preg_replace('/[^\d,.-]/', '', trim($value));

        if ($normalized === null) {
            return $normalized;
        }

        if ($normalized === '') {
            return trim($value);
        }

        if (str_contains($normalized, ',')) {
            return str_replace(',', '.', str_replace('.', '', $normalized));
        }

        return $normalized;
    }
}
