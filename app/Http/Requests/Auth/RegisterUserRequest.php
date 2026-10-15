<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Rules\Cnpj;
use App\Rules\Cpf;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'confirmed', Password::defaults()],
            'business_trade_name' => ['required', 'string', 'max:255'],
            'business_legal_name' => ['required', 'string', 'max:255'],
            'business_document' => [
                'bail',
                'required',
                'string',
                'regex:/^(?:\d{11}|[A-Z0-9]{12}\d{2})$/',
                new Cpf,
                new Cnpj,
                Rule::unique('businesses', 'document'),
            ],
            'business_phone' => ['required', 'string', 'regex:/^\d{10,11}$/'],
            'business_email' => ['required', 'email', 'max:255'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $businessDocument = preg_replace(
            '/[^A-Z0-9]/',
            '',
            Str::upper((string) $this->input('business_document')),
        ) ?? '';
        $businessPhone = preg_replace('/\D/', '', (string) $this->input('business_phone')) ?? '';

        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => Str::lower(trim((string) $this->input('email'))),
            'business_trade_name' => trim((string) $this->input('business_trade_name')),
            'business_legal_name' => trim((string) $this->input('business_legal_name')),
            'business_document' => $businessDocument,
            'business_phone' => $businessPhone,
            'business_email' => Str::lower(trim((string) $this->input('business_email'))),
        ]);
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Informe seu nome completo.',
            'email.required' => 'Informe seu e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está cadastrado.',
            'password.required' => 'Informe uma senha.',
            'password.confirmed' => 'A confirmação da senha não corresponde.',
            'password.min' => 'A senha deve ter pelo menos :min caracteres.',
            'business_trade_name.required' => 'Informe o nome fantasia da empresa.',
            'business_legal_name.required' => 'Informe a razão social da empresa.',
            'business_document.required' => 'Informe o CPF ou CNPJ da empresa.',
            'business_document.regex' => 'Informe um CPF com 11 dígitos ou CNPJ com 14 caracteres.',
            'business_document.unique' => 'Este CPF/CNPJ já está cadastrado.',
            'business_phone.required' => 'Informe o telefone da empresa.',
            'business_phone.regex' => 'Informe um telefone com 10 ou 11 dígitos.',
            'business_email.required' => 'Informe o e-mail da empresa.',
            'business_email.email' => 'Informe um e-mail empresarial válido.',
        ];
    }
}
