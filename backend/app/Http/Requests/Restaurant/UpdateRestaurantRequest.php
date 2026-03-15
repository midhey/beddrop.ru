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
            'description'   => ['sometimes', 'nullable', 'string'],
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
            'address'             => ['sometimes', 'array'],
            'address.label'       => ['sometimes', 'nullable', 'string', 'max:255'],
            'address.line1'       => ['sometimes', 'required', 'string', 'max:255'],
            'address.line2'       => ['sometimes', 'nullable', 'string', 'max:255'],
            'address.city'        => ['sometimes', 'nullable', 'string', 'max:255'],
            'address.postal_code' => ['sometimes', 'nullable', 'string', 'max:32'],
            'address.lat'         => ['sometimes', 'nullable', 'numeric'],
            'address.lng'         => ['sometimes', 'nullable', 'numeric'],
        ];
    }
}
