<?php

namespace App\Http\Requests\Restaurant;

use Illuminate\Foundation\Http\FormRequest;

class StoreRestaurantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:255'],
            'slug'          => ['nullable', 'string', 'max:255', 'unique:restaurants,slug'],
            'phone'         => ['nullable', 'string', 'max:32'],
            'is_active'     => ['sometimes', 'boolean'],
            'prep_time_min' => ['nullable', 'integer', 'min:0'],
            'prep_time_max' => ['nullable', 'integer', 'min:0'],
            'logo_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'address.line1'       => ['required', 'string', 'max:255'],
            'address.line2'       => ['nullable', 'string', 'max:255'],
            'address.city'        => ['nullable', 'string', 'max:255'],
            'address.postal_code' => ['nullable', 'string', 'max:32'],
            'address.lat'         => ['nullable', 'numeric'],
            'address.lng'         => ['nullable', 'numeric'],
            'owner_id' => ['nullable', 'exists:users,id'],
        ];
    }
}
