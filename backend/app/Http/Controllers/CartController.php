<?php

namespace App\Http\Controllers;

use App\Http\Requests\Cart\AddCartItemRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        $cart = Cart::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->with([
                'restaurant',
                'items.product.images.media',
            ])
            ->first();

        if(!$cart) {
            return response()->json([
                'cart' => null,
            ]);
        }

        return response()->json([
            'cart' => new CartResource($cart),
        ]);
    }

    public function addItem(AddCartItemRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        $quantity = $data['quantity'] ?? 1;

        $product = Product::query()
            ->with('restaurant')
            ->where('id', $data['product_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $cart = Cart::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if(!$cart) {
            $cart = Cart::create([
                'user_id' => $user->id,
                'restaurant_id' => $product->restaurant_id,
                'status' => 'ACTIVE',
                'is_active' => true,
            ]);
        } else {
            if($cart->restaurant_id && $cart->restaurant_id !== $product->restaurant_id) {
                return response()->json([
                    'message' => 'В корзине уже есть товары из другого ресторана. Очистите корзину, чтобы сменить ресторан.',
                ], 422);
            }

            if(!$cart->restaurant_id) {
                $cart->restaurant_id = $product->restaurant_id;
                $cart->save();
            }
        }

        $item = $cart->items()
            ->where('product_id', $product->id)
            ->first();

        if($item) {
            $item->quantity += $quantity;
            $item->unit_price_snapshot = $product->price;
            $item->save();
        } else {
            $item = $cart->items()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price_snapshot' => $product->price,
            ]);
        }

        $cart->load(['restaurant', 'items.product.images.media']);

        return response()->json([
            'cart' => new CartResource($cart),
        ], 201);
    }

    public function updateItem(UpdateCartItemRequest $request, CartItem $item)
    {
        $user = $request->user();
        $data = $request->validated();

        $cart = $item->cart;

        if(!$cart || $cart->user_id !== $user->id || !$cart->is_active) {
            abort(404);
        }

        $quantity = (int)$data['quantity'];

        if($quantity <= 0) {
            $item->delete();
        } else {
            $item->quantity = $quantity;
            $item->save();
        }

        $cart->load(['restaurant', 'items.product.images.media']);

        if($cart->items()->count() === 0) {
            $cart->restaurant_id = null;
            $cart->save();
        }

        return response()->json([
            'cart' => new CartResource($cart),
        ]);
    }

    public function removeItem(Request $request, CartItem $item)
    {
        $user = $request->user();

        $cart = $item->cart;

        if(!$cart || $cart->user_id !== $user->id || !$cart->is_active) {
            abort(404);
        }

        $item->delete();

        $cart->load(['restaurant', 'items.product.images.media']);

        return response()->json([
            'cart' => new CartResource($cart),
        ]);
    }

    public function clear(Request $request)
    {
        $user = $request->user();

        $cart = Cart::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if(!$cart) {
            return response()->noContent();
        }

        $cart->items()->delete();
        $cart->status = 'ABANDONED';
        $cart->is_active = false;
        $cart->save();

        return response()->noContent();
    }
}
