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
use RuntimeException;
use Throwable;

class CreateOrder
{
    public function __construct(
        private readonly DeliveryQuoteService $quotes,
    ) {
    }

    public function __invoke(User $user, array $data): Order
    {
        return DB::transaction(function () use ($user, $data) {
            $cart = Cart::query()
                ->where('user_id', $user->id)
                ->where('status', CartStatus::ACTIVE->value)
                ->where('is_active', true)
                ->with([
                    'items.product',
                    'restaurant.address',
                ])
                ->lockForUpdate()
                ->first();

            if (!$cart || $cart->status !== CartStatus::ACTIVE->value || !$cart->is_active || $cart->items->isEmpty()) {
                throw new HttpResponseException(response()->json([
                    'message' => 'Активная корзина пуста.',
                ], 422));
            }

            if (!$cart->restaurant_id) {
                throw new HttpResponseException(response()->json([
                    'message' => 'Корзина не привязана к ресторану.',
                ], 422));
            }

            if (!$cart->restaurant || !$cart->restaurant->isOpenForOrders()) {
                throw new HttpResponseException(response()->json([
                    'message' => $this->closedRestaurantMessage($cart->restaurant?->availability()),
                ], 422));
            }

            $total = 0;
            foreach ($cart->items as $item) {
                $total += (float) $item->unit_price_snapshot * (int) $item->quantity;
            }

            $paymentMethod = isset($data['payment_method'])
                ? PaymentMethod::from($data['payment_method'])
                : PaymentMethod::ONLINE;

            if ($paymentMethod !== PaymentMethod::ONLINE) {
                throw new HttpResponseException(response()->json([
                    'message' => 'Сейчас доступна только онлайн-оплата. Оплата наличными и картой курьеру временно отключены.',
                ], 422));
            }

            $quote = $this->buildQuote($user, $cart, $data);
            $deliveryPrice = (float) $quote['delivery_price'];
            $deliveryAddress = $quote['delivery_address'];

            $order = Order::create([
                'user_id' => $user->id,
                'restaurant_id' => $cart->restaurant_id,
                'courier_id' => null,
                'status' => OrderStatus::CREATED->value,
                'payment_status' => PaymentStatus::PENDING->value,
                'payment_method' => $paymentMethod->value,
                'total_price' => $total + $deliveryPrice,
                'comment' => $data['comment'] ?? null,
                'delivery_address_id' => $deliveryAddress->id,
                'delivery_lat' => $deliveryAddress?->lat,
                'delivery_lng' => $deliveryAddress?->lng,
                'delivery_distance_meters' => $quote['distance_meters'],
                'delivery_duration_seconds' => $quote['duration_seconds'],
                'delivery_price_snapshot' => $quote['delivery_price'],
                'estimated_pickup_at' => null,
                'estimated_delivery_at' => null,
                'logistics_snapshot_json' => [
                    'price' => $quote['price'],
                    'time' => $quote['time'],
                    'settings' => $quote['settings_snapshot'],
                ],
            ]);

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
     * @return array<string, mixed>
     */
    private function buildQuote(User $user, Cart $cart, array $data): array
    {
        $address = $user->addresses()->find($data['delivery_address_id'] ?? null);

        if (!$address) {
            throw new HttpResponseException(response()->json([
                'message' => 'Адрес доставки не найден.',
            ], 422));
        }

        try {
            $quote = $this->quotes->quote($cart->restaurant, $address);
            $quote['delivery_address'] = $address;

            return $quote;
        } catch (RuntimeException $exception) {
            throw new HttpResponseException(response()->json([
                'message' => $exception->getMessage(),
            ], 422));
        } catch (Throwable) {
            throw new HttpResponseException(response()->json([
                'message' => 'Не удалось рассчитать доставку.',
            ], 422));
        }
    }

    /**
     * @param array<string, mixed>|null $availability
     */
    private function closedRestaurantMessage(?array $availability): string
    {
        if (!empty($availability['closed_reason'])) {
            return 'Ресторан сейчас не принимает заказы: ' . $availability['closed_reason'];
        }

        return match ($availability['status'] ?? null) {
            'inactive' => 'Ресторан сейчас недоступен для заказов.',
            'manually_closed' => 'Ресторан сейчас не принимает заказы.',
            'closed_by_schedule' => 'Ресторан сейчас закрыт для заказов.',
            default => 'Ресторан сейчас не принимает заказы.',
        };
    }
}
