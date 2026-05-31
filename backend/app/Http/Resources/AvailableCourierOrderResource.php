<?php

namespace App\Http\Resources;

use App\Services\Logistics\CourierPayoutCalculator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AvailableCourierOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $deliveryArea = $this->coarseDeliveryArea();

        return [
            'id' => $this->id,
            'restaurant_id' => $this->restaurant_id,
            'status' => $this->status,
            'total_price' => $this->total_price,
            'courier_fee' => $this->courier_fee,
            'courier_estimated_fee' => number_format(
                app(CourierPayoutCalculator::class)->calculate($this->resource),
                2,
                '.',
                '',
            ),
            'delivery_distance_meters' => $this->delivery_distance_meters,
            'delivery_duration_seconds' => $this->delivery_duration_seconds,
            'delivery_price_snapshot' => $this->delivery_price_snapshot,
            'estimated_pickup_at' => $this->estimated_pickup_at,
            'estimated_delivery_at' => $this->estimated_delivery_at,
            'courier_approach_distance_meters' => $this->when(
                $this->getAttribute('courier_approach_distance_meters') !== null,
                $this->getAttribute('courier_approach_distance_meters'),
            ),
            'delivery_area' => $deliveryArea,
            'delivery_address' => $deliveryArea ? [
                'value' => $deliveryArea,
                'city' => $this->deliveryAddress?->city,
                'area' => $this->deliveryAddress?->area,
                'settlement' => $this->deliveryAddress?->settlement,
            ] : null,
            'restaurant' => new RestaurantResource($this->whenLoaded('restaurant')),
            'items_count' => $this->items_count,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function coarseDeliveryArea(): ?string
    {
        $address = $this->deliveryAddress;

        if (! $address) {
            return null;
        }

        $parts = array_filter([
            $address->city,
            $address->settlement,
            $address->area,
            $address->region,
        ]);

        return $parts ? implode(', ', array_values(array_unique($parts))) : null;
    }
}
