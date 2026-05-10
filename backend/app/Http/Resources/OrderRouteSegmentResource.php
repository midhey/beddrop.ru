<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderRouteSegmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'segment_type' => $this->segment_type,
            'mode' => $this->mode,
            'distance_meters' => $this->distance_meters,
            'duration_seconds' => $this->duration_seconds,
            'encoded_shape' => $this->encoded_shape,
            'raw_response' => $this->raw_response_json,
            'settings_snapshot' => $this->settings_snapshot_json,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
