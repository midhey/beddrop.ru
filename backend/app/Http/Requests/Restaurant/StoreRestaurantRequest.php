<?php

namespace App\Http\Requests\Restaurant;

use App\Http\Requests\Concerns\ValidatesAddressPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            'accepts_orders' => ['sometimes', 'boolean'],
            'timezone'      => ['sometimes', 'string', 'timezone'],
            'opens_at'      => ['nullable', 'date_format:H:i'],
            'closes_at'     => ['nullable', 'date_format:H:i'],
            'closed_reason' => ['nullable', 'string', 'max:255'],
            'prep_time_min' => ['nullable', 'integer', 'min:0'],
            'prep_time_max' => ['nullable', 'integer', 'min:0'],
            'logo_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'owner_id' => ['nullable', 'exists:users,id'],
        ] + $this->addressRules('address.');
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $min = $this->input('prep_time_min');
            $max = $this->input('prep_time_max');

            if ($min !== null && $max !== null && (int) $max < (int) $min) {
                $validator->errors()->add('prep_time_max', 'Максимальное время приготовления не может быть меньше минимального.');
            }
        });
    }
}
