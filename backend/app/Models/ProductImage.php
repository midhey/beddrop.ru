<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $fillable = [
        'product_id',
        'media_id',
        'sort_order',
        'is_cover',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_cover'   => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function media()
    {
        return $this->belongsTo(Media::class);
    }
}
