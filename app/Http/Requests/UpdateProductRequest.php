<?php

namespace App\Http\Requests;

use App\Services\BusinessContext;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends StoreProductRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['sku'] = [
            'required',
            'string',
            'max:100',
            Rule::unique('products', 'sku')
                ->where(fn ($query) => $query->where('business_id', app(BusinessContext::class)->id()))
                ->ignore($this->route('product')),
        ];
        $rules['active'] = ['required', 'boolean'];

        return $rules;
    }
}
