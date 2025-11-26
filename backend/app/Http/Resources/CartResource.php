<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $items = $this->whenLoaded('items', function () {
            return CartItemResource::collection($this->items);
        });

        $itemsArray = $items instanceof \Illuminate\Http\Resources\Json\AnonymousResourceCollection
            ? $items->toArray(request())
            : [];

        $itemsCount = array_sum(array_column($itemsArray, 'quantity'));
        $totalPrice = array_sum(array_column($itemsArray, 'subtotal'));

        return [
            'id' => $this->id,
            'status' => $this->status,
            'is_active' => (bool)$this->is_active,

            'restaurant' => new RestaurantResource($this->whenLoaded('restaurant')),
            'items' => $items,

            'items_count' => $itemsCount,
            'total_price' => $totalPrice,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
