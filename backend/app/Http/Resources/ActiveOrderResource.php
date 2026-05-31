<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActiveOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'total_price' => $this->total_price,
            'courier_fee' => $this->courier_fee,
            'delivery_distance_meters' => $this->delivery_distance_meters,
            'delivery_duration_seconds' => $this->delivery_duration_seconds,
            'delivery_price_snapshot' => $this->delivery_price_snapshot,
            'estimated_pickup_at' => $this->estimated_pickup_at,
            'estimated_delivery_at' => $this->estimated_delivery_at,
            'restaurant' => $this->restaurant ? [
                'id' => $this->restaurant->id,
                'name' => $this->restaurant->name,
                'slug' => $this->restaurant->slug,
            ] : null,
            'items_count' => (int) ($this->items_count ?? 0),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
