<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductCategoryRequest;
use App\Http\Requests\Product\UpdateProductCategoryRequest;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductCategory::query()
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($search = $request->query('search')) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $categories = $query->get();

        return response()->json([
            'categories' => $categories,
        ]);
    }

    public function store(StoreProductCategoryRequest $request)
    {
        $user = $request->user();
        if (! $user || ! $user->isAdmin()) {
            abort(403);
        }

        $data = $request->validated();

        $category = ProductCategory::create($data);

        return response()->json([
            'category' => $category,
        ], 201);
    }

    public function update(UpdateProductCategoryRequest $request, ProductCategory $category)
    {
        $user = $request->user();
        if (! $user || ! $user->isAdmin()) {
            abort(403);
        }

        $data = $request->validated();

        $category->fill($data);
        $category->save();

        return response()->json([
            'category' => $category,
        ]);
    }

    public function destroy(Request $request, ProductCategory $category)
    {
        $user = $request->user();
        if (! $user || ! $user->isAdmin()) {
            abort(403);
        }

        $category->delete();

        return response()->json([
            'message' => 'Категория удалена',
        ]);
    }
}
