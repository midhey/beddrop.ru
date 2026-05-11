<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RestaurantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'slug'          => $this->slug,
            'phone'         => $this->phone,
            'is_active'     => (bool) $this->is_active,
            'accepts_orders' => (bool) ($this->accepts_orders ?? true),
            'timezone'      => $this->timezone ?: 'Europe/Moscow',
            'opens_at'      => $this->availability()['opens_at'],
            'closes_at'     => $this->availability()['closes_at'],
            'closed_reason' => $this->closed_reason,
            'availability'  => $this->availability(),
            'prep_time_min' => $this->prep_time_min,
            'prep_time_max' => $this->prep_time_max,
            'prep_time_avg_minutes' => $this->prepTimeAverageMinutes(),

            'address_id'    => $this->address_id,
            'address'       => new AddressResource($this->whenLoaded('address')),

            'logo'          => $this->whenLoaded('logo', function () {
                return [
                    'id'  => $this->logo->id,
                    'url' => $this->logo->url,
                ];
            }),

            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }
}
