<?php

namespace App\Http\Requests\Restaurant;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRestaurantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $restaurantId = $this->route('restaurant')?->id;

        return [
            'name'          => ['sometimes', 'string', 'max:255'],
            'slug'          => [
                'sometimes',
                'string',
                'max:255',
                'unique:restaurants,slug,' . $restaurantId,
            ],
            'address_id'    => ['sometimes', 'nullable', 'integer', 'exists:addresses,id'],
            'phone'         => ['sometimes', 'nullable', 'string', 'max:32'],
            'is_active'     => ['sometimes', 'boolean'],
            'prep_time_min' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'prep_time_max' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'logo_media_id' => ['sometimes', 'nullable', 'integer', 'exists:media,id'],
        ];
    }
}
