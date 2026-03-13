<?php

namespace App\Http\Requests\Order;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'delivery_address_id' => [
                'nullable',
                'integer',
                Rule::exists('addresses', 'id')->where(fn ($query) => $query->where('user_id', $this->user()->id)),
            ],
            'comment' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
        ];
    }
}
