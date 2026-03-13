<?php

namespace App\Actions\Cart;

use App\Enums\CartStatus;
use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;

class AddCartItem
{
    public function __invoke(User $user, array $data): Cart
    {
        $quantity = $data['quantity'] ?? 1;

        $product = Product::query()
            ->with('restaurant')
            ->where('id', $data['product_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $cart = Cart::query()
            ->where('user_id', $user->id)
            ->where('status', CartStatus::ACTIVE->value)
            ->where('is_active', true)
            ->first();

        if (!$cart) {
            $cart = Cart::create([
                'user_id' => $user->id,
                'restaurant_id' => $product->restaurant_id,
                'status' => CartStatus::ACTIVE->value,
                'is_active' => true,
            ]);
        } else {
            if ($cart->restaurant_id && $cart->restaurant_id !== $product->restaurant_id) {
                throw new HttpResponseException(response()->json([
                    'message' => 'В корзине уже есть товары из другого ресторана. Очистите корзину, чтобы сменить ресторан.',
                ], 422));
            }

            if (!$cart->restaurant_id) {
                $cart->restaurant_id = $product->restaurant_id;
                $cart->save();
            }
        }

        $item = $cart->items()
            ->where('product_id', $product->id)
            ->first();

        if ($item) {
            $item->quantity += $quantity;
            $item->unit_price_snapshot = $product->price;
            $item->save();
        } else {
            $cart->items()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price_snapshot' => $product->price,
            ]);
        }

        return $cart->load(['restaurant', 'items.product.images.media']);
    }
}
