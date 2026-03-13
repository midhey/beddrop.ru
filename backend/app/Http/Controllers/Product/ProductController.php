<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use AuthorizesRequests;

    public function index(Restaurant $restaurant, Request $request)
    {
        $this->authorize('view', $restaurant);

        $perPage = min($request->integer('per_page', 20), 100);

        $query = $restaurant->products()
            ->with(['category', 'images.media']);

        if (! $this->canPreviewHiddenProducts($request->user(), $restaurant)) {
            $query->where('is_active', true);
        }

        $products = $query->paginate($perPage);

        return ProductResource::collection($products);
    }

    public function show(Request $request, Restaurant $restaurant, Product $product)
    {
        $this->authorize('view', $restaurant);

        if ($product->restaurant_id !== $restaurant->id) {
            abort(404);
        }

        if (! $product->is_active && ! $this->canPreviewHiddenProducts($request->user(), $restaurant)) {
            abort(404);
        }

        $product->load(['category', 'images.media']);

        return new ProductResource($product);
    }

    public function store(StoreProductRequest $request, Restaurant $restaurant)
    {
        $this->authorize('update', $restaurant);

        $data = $request->validated();

        $product = $restaurant->products()->create([
            'category_id' => $data['category_id'],
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'price'       => $data['price'],
            'is_active'   => $data['is_active'] ?? true,
        ]);

        $product->load(['category', 'images.media']);

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateProductRequest $request, Restaurant $restaurant, Product $product)
    {
        if ($product->restaurant_id !== $restaurant->id) {
            abort(404);
        }

        $this->authorize('update', $restaurant);

        $data = $request->validated();

        $product->fill($data);
        $product->save();

        $product->load(['category', 'images.media']);

        return new ProductResource($product);
    }

    public function destroy(Restaurant $restaurant, Product $product)
    {
        if ($product->restaurant_id !== $restaurant->id) {
            abort(404);
        }

        $this->authorize('update', $restaurant);

        $product->delete();

        return response()->json([
            'message' => 'Продукт удалён',
        ]);
    }

    private function canPreviewHiddenProducts(?User $user, Restaurant $restaurant): bool
    {
        return $user !== null
            && ($user->isAdmin() || $user->isStaffOf($restaurant));
    }
}
