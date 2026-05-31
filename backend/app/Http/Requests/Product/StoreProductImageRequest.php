<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class StoreProductImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'media_id'   => ['required', 'integer', $this->ownedMediaRule()],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_cover'   => ['nullable', 'boolean'],
        ];
    }

    private function ownedMediaRule(): Exists
    {
        $rule = Rule::exists('media', 'id');
        $user = $this->user();

        if ($user && ! $user->isAdmin()) {
            $restaurant = $this->route('restaurant');

            $rule->where(function ($query) use ($user, $restaurant) {
                $query->where('uploaded_by_user_id', $user->id);

                if ($restaurant) {
                    $query->orWhereIn('uploaded_by_user_id', function ($subQuery) use ($restaurant) {
                        $subQuery
                            ->select('user_id')
                            ->from('restaurant_user')
                            ->where('restaurant_id', $restaurant->id);
                    });
                }
            });
        }

        return $rule;
    }
}
