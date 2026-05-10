<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $items = $this->whenLoaded('items', function () {
            return OrderItemResource::collection($this->items);
        });

        $itemsArray = $items instanceof \Illuminate\Http\Resources\Json\AnonymousResourceCollection
            ? $items->toArray(request())
            : [];

        $itemsCount = array_sum(array_column($itemsArray, 'quantity'));
        $totalPrice = array_sum(array_column($itemsArray, 'subtotal'));

        return [
            'id' => $this->id,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'total_price' => $this->total_price,
            'courier_fee' => $this->courier_fee,
            'comment' => $this->comment,
            'delivery_address_id' => $this->delivery_address_id,
            'delivery_lat' => $this->delivery_lat,
            'delivery_lng' => $this->delivery_lng,
            'delivery_distance_meters' => $this->delivery_distance_meters,
            'delivery_duration_seconds' => $this->delivery_duration_seconds,
            'delivery_price_snapshot' => $this->delivery_price_snapshot,
            'estimated_pickup_at' => $this->estimated_pickup_at,
            'estimated_delivery_at' => $this->estimated_delivery_at,
            'logistics_snapshot' => $this->logistics_snapshot_json,
            'courier_approach_distance_meters' => $this->when(
                $this->getAttribute('courier_approach_distance_meters') !== null,
                $this->getAttribute('courier_approach_distance_meters'),
            ),

            'delivery_address' => new AddressResource(
                $this->whenLoaded('deliveryAddress')
            ),

            'restaurant' => new RestaurantResource($this->whenLoaded('restaurant')),
            'items' => $items,
            'events' => OrderEventResource::collection($this->whenLoaded('events')),
            'route_segments' => OrderRouteSegmentResource::collection($this->whenLoaded('routeSegments')),

            'items_count' => $itemsCount,
            'calculated_total' => $totalPrice,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
