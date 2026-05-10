<?php

namespace App\Http\Requests\Auth;

use App\Enums\AuthClientType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'client_type' => ['sometimes', Rule::enum(AuthClientType::class)],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
