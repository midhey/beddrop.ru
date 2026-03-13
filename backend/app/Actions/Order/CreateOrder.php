<?php

namespace App\Actions\Order;

use App\Enums\CartStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

class CreateOrder
{
    public function __invoke(User $user, array $data): Order
    {
        $cart = Cart::query()
            ->where('user_id', $user->id)
            ->where('status', CartStatus::ACTIVE->value)
            ->where('is_active', true)
            ->with([
                'items.product',
                'restaurant',
            ])
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            throw new HttpResponseException(response()->json([
                'message' => 'Активная корзина пуста.',
            ], 422));
        }

        if (!$cart->restaurant_id) {
            throw new HttpResponseException(response()->json([
                'message' => 'Корзина не привязана к ресторану.',
            ], 422));
        }

        $total = 0;
        foreach ($cart->items as $item) {
            $total += (float) $item->unit_price_snapshot * (int) $item->quantity;
        }

        return DB::transaction(function () use ($cart, $user, $data, $total) {
            $paymentMethod = isset($data['payment_method'])
                ? PaymentMethod::from($data['payment_method'])
                : PaymentMethod::CASH;

            $order = Order::create([
                'user_id' => $user->id,
                'restaurant_id' => $cart->restaurant_id,
                'courier_id' => null,
                'status' => OrderStatus::CREATED->value,
                'payment_status' => PaymentStatus::PENDING->value,
                'payment_method' => $paymentMethod->value,
                'total_price' => $total,
                'comment' => $data['comment'] ?? null,
                'delivery_address_id' => $data['delivery_address_id'] ?? null,
                'delivery_lat' => null,
                'delivery_lng' => null,
            ]);

            foreach ($cart->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'name_snapshot' => $item->product->name,
                    'unit_price_snapshot' => $item->unit_price_snapshot,
                    'quantity' => $item->quantity,
                ]);
            }

            OrderEvent::create([
                'order_id' => $order->id,
                'event' => OrderStatus::CREATED->value,
                'payload' => [
                    'cart_id' => $cart->id,
                ],
            ]);

            $cart->status = CartStatus::ORDERED->value;
            $cart->is_active = false;
            $cart->save();

            return $order->load([
                'restaurant',
                'items.product.images.media',
                'events',
            ]);
        });
    }
}
