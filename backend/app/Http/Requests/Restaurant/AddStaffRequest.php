<?php

namespace App\Http\Requests\Restaurant;

use Illuminate\Foundation\Http\FormRequest;

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
            'role'    => ['required', 'string', 'in:OWNER,MANAGER,STAFF'],
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
