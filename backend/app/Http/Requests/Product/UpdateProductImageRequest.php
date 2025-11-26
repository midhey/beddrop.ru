<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_cover'   => ['sometimes', 'boolean'],
        ];
    }
}
