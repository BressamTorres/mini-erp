<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize formatted values while preserving their string representation.
     */
    protected function prepareForValidation(): void
    {
        $document = $this->input('document');

        $this->merge([
            'document' => $document === null || $document === ''
                ? null
                : $this->removeFormatting($document),
            'phone' => $this->removeFormatting($this->input('phone', '')),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'document' => ['nullable', 'string', 'regex:/^(?:\d{11}|\d{14})$/'],
            'phone' => ['required', 'string', 'regex:/^\d{10,11}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'photo' => ['bail', 'nullable', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'image', 'max:2048'],
            'remove_photo' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get the validation error messages.
     *
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
            'photo.mimes' => 'A foto deve estar no formato JPG, JPEG, PNG ou WEBP.',
            'photo.extensions' => 'A foto deve estar no formato JPG, JPEG, PNG ou WEBP.',
            'photo.image' => 'O arquivo enviado deve ser uma imagem válida.',
            'photo.max' => 'A foto deve ter no máximo 2 MB.',
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
