<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'restaurant_id' => $this->restaurant_id,
            'category'      => $this->whenLoaded('category', function () {
                return [
                    'id'         => $this->category->id,
                    'slug'       => $this->category->slug,
                    'name'       => $this->category->name,
                    'sort_order' => $this->category->sort_order,
                ];
            }),
            'name'          => $this->name,
            'description'   => $this->description,
            'price'         => $this->price,
            'is_active'     => (bool) $this->is_active,

            'images'        => ProductImageResource::collection(
                $this->whenLoaded('images')
            ),

            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }
}
