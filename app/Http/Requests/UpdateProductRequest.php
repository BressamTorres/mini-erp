<?php

namespace App\Http\Requests;

class UpdateProductRequest extends StoreProductRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['active'] = ['required', 'boolean'];

        return $rules;
    }
}
