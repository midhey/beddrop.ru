<?php

namespace App\Http\Requests\Restaurant;

use App\Http\Requests\Concerns\ValidatesAddressPayload;
use Illuminate\Foundation\Http\FormRequest;

class StoreRestaurantRequest extends FormRequest
{
    use ValidatesAddressPayload;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'slug'          => ['nullable', 'string', 'max:255', 'unique:restaurants,slug'],
            'phone'         => ['nullable', 'string', 'max:32'],
            'is_active'     => ['sometimes', 'boolean'],
            'prep_time_min' => ['nullable', 'integer', 'min:0'],
            'prep_time_max' => ['nullable', 'integer', 'min:0'],
            'logo_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'owner_id' => ['nullable', 'exists:users,id'],
        ] + $this->addressRules('address.');
    }
}
