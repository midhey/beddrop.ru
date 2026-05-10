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
use App\Models\OrderRouteSegment;
use App\Models\User;
use App\Services\Logistics\DeliveryQuoteService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Throwable;

class CreateOrder
{
    public function __construct(
        private readonly DeliveryQuoteService $quotes,
    ) {
    }

    public function __invoke(User $user, array $data): Order
    {
        $cart = Cart::query()
            ->where('user_id', $user->id)
            ->where('status', CartStatus::ACTIVE->value)
            ->where('is_active', true)
            ->with([
                'items.product',
                'restaurant.address',
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

            $quote = $this->tryBuildQuote($cart, $data);
            $deliveryPrice = (float) ($quote['delivery_price'] ?? 0);
            $deliveryAddress = $quote['delivery_address'] ?? null;
            $estimatedPickupAt = $quote ? now()->addMinutes($quote['time']['prep'] + $quote['time']['pickup_buffer']) : null;
            $estimatedDeliveryAt = $quote ? now()->addMinutes($quote['eta_minutes']) : null;

            $order = Order::create([
                'user_id' => $user->id,
                'restaurant_id' => $cart->restaurant_id,
                'courier_id' => null,
                'status' => OrderStatus::CREATED->value,
                'payment_status' => PaymentStatus::PENDING->value,
                'payment_method' => $paymentMethod->value,
                'total_price' => $total + $deliveryPrice,
                'comment' => $data['comment'] ?? null,
                'delivery_address_id' => $data['delivery_address_id'] ?? null,
                'delivery_lat' => $deliveryAddress?->lat,
                'delivery_lng' => $deliveryAddress?->lng,
                'delivery_distance_meters' => $quote['distance_meters'] ?? null,
                'delivery_duration_seconds' => $quote['duration_seconds'] ?? null,
                'delivery_price_snapshot' => $quote['delivery_price'] ?? null,
                'estimated_pickup_at' => $estimatedPickupAt,
                'estimated_delivery_at' => $estimatedDeliveryAt,
                'logistics_snapshot_json' => $quote ? [
                    'price' => $quote['price'],
                    'time' => $quote['time'],
                    'settings' => $quote['settings_snapshot'],
                ] : null,
            ]);

            if ($quote) {
                OrderRouteSegment::create([
                    'order_id' => $order->id,
                    'segment_type' => 'restaurant_to_client',
                    'mode' => $quote['mode'],
                    'distance_meters' => $quote['distance_meters'],
                    'duration_seconds' => $quote['duration_seconds'],
                    'encoded_shape' => $quote['route']['encoded_shape'],
                    'raw_response_json' => $quote['route']['raw'],
                    'settings_snapshot_json' => $quote['settings_snapshot'],
                ]);
            }

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
                'deliveryAddress',
                'routeSegments',
            ]);
        });
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>|null
     */
    private function tryBuildQuote(Cart $cart, array $data): ?array
    {
        if (empty($data['delivery_address_id']) || !config('services.valhalla.url')) {
            return null;
        }

        $address = $cart->user
            ? $cart->user->addresses()->find($data['delivery_address_id'])
            : null;

        if (!$address) {
            return null;
        }

        try {
            $quote = $this->quotes->quote($cart->restaurant, $address);
            $quote['delivery_address'] = $address;

            return $quote;
        } catch (Throwable) {
            return null;
        }
    }
}
