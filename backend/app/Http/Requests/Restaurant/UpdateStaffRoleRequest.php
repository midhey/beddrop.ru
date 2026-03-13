<?php

namespace App\Http\Requests\Restaurant;

use App\Enums\RestaurantStaffRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStaffRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => ['required', Rule::enum(RestaurantStaffRole::class)],
        ];
    }
}
