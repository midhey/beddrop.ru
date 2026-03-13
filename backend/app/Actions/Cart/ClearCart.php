<?php

namespace App\Actions\Cart;

use App\Enums\CartStatus;
use App\Models\Cart;

class ClearCart
{
    public function __invoke(Cart $cart): void
    {
        $cart->items()->delete();
        $cart->status = CartStatus::ABANDONED->value;
        $cart->is_active = false;
        $cart->save();
    }
}
