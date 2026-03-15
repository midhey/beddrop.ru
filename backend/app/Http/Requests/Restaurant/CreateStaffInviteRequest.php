<?php

namespace App\Http\Requests\Restaurant;

use App\Enums\RestaurantStaffRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateStaffInviteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => [
                'required',
                Rule::enum(RestaurantStaffRole::class),
                'not_in:' . RestaurantStaffRole::OWNER->value,
            ],
            'expires_in_minutes' => ['nullable', 'integer', Rule::in([5, 15, 30, 60])],
        ];
    }
}
