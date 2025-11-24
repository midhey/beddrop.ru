<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('phone')) {
            $this->merge([
                'phone' => preg_replace('/\D+/', '', $this->input('phone')),
            ]);
        }
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                'unique:users,email,' . $userId
            ],
            'phone' => [
                'sometimes',
                'string',
                'digits:11',
                'unique:users,phone,' . $userId
            ]
        ];
    }

    public function messages(): array {
        return [
            'email.email'    => 'Некорректный формат электронной почты',
            'email.unique'   => 'Пользователь с такой почтой уже существует',

            'phone.unique'   => 'Пользователь с таким телефоном уже существует',
        ];
    }
}
