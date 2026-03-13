<?php

namespace App\Actions\Cart;

use App\Models\Cart;
use App\Models\CartItem;

class RemoveCartItem
{
    public function __invoke(CartItem $item): Cart
    {
        $cart = $item->cart;

        $item->delete();

        if ($cart->items()->count() === 0) {
            $cart->restaurant_id = null;
            $cart->save();
        }

        return $cart->load(['restaurant', 'items.product.images.media']);
    }
}
