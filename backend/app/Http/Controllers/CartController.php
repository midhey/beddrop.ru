<?php

namespace App\Http\Controllers;

use App\Actions\Cart\AddCartItem;
use App\Actions\Cart\ClearCart;
use App\Actions\Cart\RemoveCartItem;
use App\Actions\Cart\UpdateCartItem;
use App\Enums\CartStatus;
use App\Http\Requests\Cart\AddCartItemRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        $cart = Cart::query()
            ->where('user_id', $user->id)
            ->where('status', CartStatus::ACTIVE->value)
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

    public function addItem(AddCartItemRequest $request, AddCartItem $addCartItem)
    {
        $data = $request->validated();
        $cart = $addCartItem($request->user(), $data);

        return response()->json([
            'cart' => new CartResource($cart),
        ], 201);
    }

    public function updateItem(
        UpdateCartItemRequest $request,
        CartItem $item,
        UpdateCartItem $updateCartItem
    )
    {
        $user = $request->user();
        $data = $request->validated();

        $cart = $item->cart;

        if(!$cart || $cart->user_id !== $user->id || !$cart->is_active) {
            abort(404);
        }

        $cart = $updateCartItem($item, (int) $data['quantity']);

        return response()->json([
            'cart' => new CartResource($cart),
        ]);
    }

    public function removeItem(Request $request, CartItem $item, RemoveCartItem $removeCartItem)
    {
        $user = $request->user();

        $cart = $item->cart;

        if(!$cart || $cart->user_id !== $user->id || !$cart->is_active) {
            abort(404);
        }

        $cart = $removeCartItem($item);

        return response()->json([
            'cart' => new CartResource($cart),
        ]);
    }

    public function clear(Request $request, ClearCart $clearCart)
    {
        $user = $request->user();

        $cart = Cart::query()
            ->where('user_id', $user->id)
            ->where('status', CartStatus::ACTIVE->value)
            ->where('is_active', true)
            ->first();

        if(!$cart) {
            return response()->noContent();
        }

        $clearCart($cart);

        return response()->noContent();
    }
}
