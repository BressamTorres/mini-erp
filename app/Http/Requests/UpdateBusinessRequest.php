<?php

namespace App\Http\Requests;

use App\Rules\Cnpj;
use App\Rules\Cpf;
use App\Services\BusinessContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateBusinessRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->business_id !== null
            && (int) $user->business_id === app(BusinessContext::class)->id();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'trade_name' => ['required', 'string', 'max:255'],
            'legal_name' => ['required', 'string', 'max:255'],
            'document' => [
                'bail',
                'required',
                'string',
                'regex:/^(?:\d{11}|[A-Z0-9]{12}\d{2})$/',
                new Cpf,
                new Cnpj,
                Rule::unique('businesses', 'document')->ignore($this->user()->business_id),
            ],
            'phone' => ['required', 'string', 'regex:/^\d{10,11}$/'],
            'email' => ['required', 'email', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $document = preg_replace(
            '/[^A-Z0-9]/',
            '',
            Str::upper((string) $this->input('document')),
        ) ?? '';
        $phone = preg_replace('/\D/', '', (string) $this->input('phone')) ?? '';

        $this->merge([
            'trade_name' => trim((string) $this->input('trade_name')),
            'legal_name' => trim((string) $this->input('legal_name')),
            'document' => $document,
            'phone' => $phone,
            'email' => Str::lower(trim((string) $this->input('email'))),
        ]);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'trade_name.required' => 'Informe o nome fantasia da empresa.',
            'legal_name.required' => 'Informe a razão social da empresa.',
            'document.required' => 'Informe o CPF ou CNPJ da empresa.',
            'document.regex' => 'Informe um CPF com 11 dígitos ou CNPJ com 14 caracteres.',
            'document.unique' => 'Este CPF/CNPJ já está cadastrado.',
            'phone.required' => 'Informe o telefone da empresa.',
            'phone.regex' => 'Informe um telefone com 10 ou 11 dígitos.',
            'email.required' => 'Informe o e-mail da empresa.',
            'email.email' => 'Informe um e-mail empresarial válido.',
        ];
    }
}
