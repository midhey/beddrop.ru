<?php

namespace App\Http\Requests\Restaurant;

use App\Enums\RestaurantStaffRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role'    => ['required', Rule::enum(RestaurantStaffRole::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'Не указан пользователь',
            'user_id.exists'   => 'Пользователь не найден',
            'role.in'          => 'Неверная роль сотрудника',
        ];
    }
}
