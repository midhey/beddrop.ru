<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    protected $fillable = [
        'uploaded_by_user_id',
        'disk',
        'path',
        'mime',
        'size_bytes',
    ];

    protected $appends = ['url'];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function restaurantLogoUsages(): HasMany
    {
        return $this->hasMany(Restaurant::class, 'logo_media_id');
    }

    public function productImageUsages(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk ?? 'public')->url($this->path);
    }
}
