<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => 'required|string',
            'password' => 'required|string|confirmed|min:8',
        ];
    }

    public function messages(): array {
        return [
            'current_password.required' => 'Укажите текущий пароль',

            'password.required' => 'Укажите новый пароль',
            'password.min'      => 'Новый пароль должен содержать не менее 6 символов',
            'password.confirmed'=> 'Подтверждение пароля не совпадает',
        ];
    }
}
