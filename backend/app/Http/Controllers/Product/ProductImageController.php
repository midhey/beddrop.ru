<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductImageRequest;
use App\Http\Requests\Product\UpdateProductImageRequest;
use App\Http\Resources\ProductImageResource;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Restaurant;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ProductImageController extends Controller
{
    use AuthorizesRequests;

    protected function ensureSameRestaurant(Restaurant $restaurant, Product $product): void
    {
        if ($product->restaurant_id !== $restaurant->id) {
            abort(404);
        }
    }

    protected function ensureSameProduct(Product $product, ProductImage $image): void
    {
        if ($image->product_id !== $product->id) {
            abort(404);
        }
    }

    public function store(
        StoreProductImageRequest $request,
        Restaurant $restaurant,
        Product $product
    ) {
        $this->ensureSameRestaurant($restaurant, $product);
        $this->authorize('update', $restaurant);

        $data = $request->validated();

        // если is_cover = true — сбросим остальные
        if (!empty($data['is_cover'])) {
            ProductImage::where('product_id', $product->id)
                ->update(['is_cover' => false]);
        }

        $image = $product->images()->create([
            'media_id'   => $data['media_id'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_cover'   => $data['is_cover'] ?? false,
        ]);

        $image->load('media');

        return (new ProductImageResource($image))
            ->response()
            ->setStatusCode(201);
    }

    public function update(
        UpdateProductImageRequest $request,
        Restaurant $restaurant,
        Product $product,
        ProductImage $image
    ) {
        $this->ensureSameRestaurant($restaurant, $product);
        $this->ensureSameProduct($product, $image);
        $this->authorize('update', $restaurant);

        $data = $request->validated();

        if (array_key_exists('is_cover', $data) && $data['is_cover']) {
            ProductImage::where('product_id', $product->id)
                ->update(['is_cover' => false]);
        }

        $image->fill($data);
        $image->save();

        $image->load('media');

        return new ProductImageResource($image);
    }

    public function destroy(
        Restaurant $restaurant,
        Product $product,
        ProductImage $image
    ) {
        $this->ensureSameRestaurant($restaurant, $product);
        $this->ensureSameProduct($product, $image);
        $this->authorize('update', $restaurant);

        $image->delete();

        return response()->json([
            'message' => 'Картинка удалена',
        ]);
    }
}
