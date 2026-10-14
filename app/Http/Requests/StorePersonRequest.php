<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePersonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $document = $this->input('document');
        $normalized = [
            'document' => $document === null || $document === ''
                ? null
                : $this->removeFormatting($document),
            'phone' => $this->removeFormatting($this->input('phone', '')),
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
            'name' => ['required', 'string', 'max:255'],
            'document' => ['nullable', 'string', 'regex:/^(?:\d{11}|\d{14})$/'],
            'phone' => ['required', 'string', 'regex:/^\d{10,11}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'photo' => ['bail', 'nullable', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'image', 'max:5120'],
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
            'name.required' => 'O nome é obrigatório.',
            'name.string' => 'O nome deve ser um texto.',
            'name.max' => 'O nome deve ter no máximo 255 caracteres.',
            'document.string' => 'O CPF/CNPJ deve ser um texto.',
            'document.regex' => 'Informe um CPF com 11 dígitos ou CNPJ com 14 dígitos.',
            'phone.required' => 'O telefone é obrigatório.',
            'phone.string' => 'O telefone deve ser um texto.',
            'phone.regex' => 'Informe um telefone com 10 ou 11 dígitos.',
            'email.email' => 'Informe um e-mail válido.',
            'email.max' => 'O e-mail deve ter no máximo 255 caracteres.',
            'active.required' => 'A situação é obrigatória.',
            'active.boolean' => 'Selecione uma situação válida.',
            'photo.mimes' => 'A foto deve estar no formato JPG, JPEG, PNG ou WEBP.',
            'photo.extensions' => 'A foto deve estar no formato JPG, JPEG, PNG ou WEBP.',
            'photo.image' => 'O arquivo enviado deve ser uma imagem válida.',
            'photo.max' => 'A foto deve ter no máximo 5 MB.',
            'submission_token.uuid' => 'Não foi possível identificar este envio. Tente novamente.',
        ];
    }

    private function removeFormatting(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return str_replace(['(', ')', '.', '/', '-', ' '], '', $value);
    }
}
