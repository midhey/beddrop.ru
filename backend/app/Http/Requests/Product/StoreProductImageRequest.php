<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'media_id'   => ['required', 'integer', 'exists:media,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_cover'   => ['nullable', 'boolean'],
        ];
    }
}
