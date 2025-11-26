<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $subtotal = (float)$this->unit_price_snapshot * (int)$this->quantity;

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'name_snapshot' => $this->name_snapshot,
            'unit_price_snapshot' => $this->unit_price_snapshot,
            'quantity' => $this->quantity,
            'subtotal' => $subtotal,

            'product' => new ProductResource($this->whenLoaded('product')),
        ];
    }
}
