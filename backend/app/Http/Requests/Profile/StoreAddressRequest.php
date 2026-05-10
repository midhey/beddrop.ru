<?php

namespace App\Http\Requests\Profile;

use App\Http\Requests\Concerns\ValidatesAddressPayload;
use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
{
    use ValidatesAddressPayload;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return $this->addressRules();
    }
}
