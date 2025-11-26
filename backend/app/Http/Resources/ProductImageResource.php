<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'sort_order' => $this->sort_order,
            'is_cover'   => (bool) $this->is_cover,
            'media'      => [
                'id'         => $this->media->id,
                'url'        => $this->media->url,
                'mime'       => $this->media->mime,
                'size_bytes' => $this->media->size_bytes,
            ],
        ];
    }
}
