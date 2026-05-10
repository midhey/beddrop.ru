<?php

namespace App\Http\Requests\Auth;

use App\Enums\AuthClientType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
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
        return [
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'digits:11', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'name' => ['nullable', 'string', 'max:255'],
            'client_type' => ['sometimes', Rule::enum(AuthClientType::class)],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
