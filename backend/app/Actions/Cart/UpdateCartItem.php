<?php

namespace App\Actions\Cart;

use App\Models\Cart;
use App\Models\CartItem;

class UpdateCartItem
{
    public function __invoke(CartItem $item, int $quantity): Cart
    {
        $cart = $item->cart;

        if ($quantity <= 0) {
            $item->delete();
        } else {
            $item->quantity = $quantity;
            $item->save();
        }

        if ($cart->items()->count() === 0) {
            $cart->restaurant_id = null;
            $cart->save();
        }

        return $cart->load(['restaurant', 'items.product.images.media']);
    }
}
