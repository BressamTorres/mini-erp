<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DashboardRequest extends FormRequest
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
        $endDateRules = ['nullable', 'date_format:Y-m-d'];

        if ($this->filled('start_date')) {
            $endDateRules[] = 'after_or_equal:start_date';
        }

        return [
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => $endDateRules,
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'start_date.date_format' => 'A data inicial deve ser uma data válida.',
            'end_date.date_format' => 'A data final deve ser uma data válida.',
            'end_date.after_or_equal' => 'A data final deve ser igual ou posterior à data inicial.',
        ];
    }
}
