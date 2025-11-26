<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'delivery_address_id' => ['nullable', 'integer', 'exists:addresses,id'],
            'comment' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['required', 'string', 'in:CASH,CARD,ONLINE'],
        ];
    }
}
