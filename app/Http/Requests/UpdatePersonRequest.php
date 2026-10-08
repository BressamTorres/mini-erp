<?php

namespace App\Http\Requests;

class UpdatePersonRequest extends StorePersonRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'active' => ['required', 'boolean'],
        ];
    }
}
