<?php

namespace App\Http\Controllers;

use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{

    public function index(Request $request)
    {
        $user = $request->user();

        $orders = Order::query()
            ->where('user_id', $user->id)
            ->with([
                'restaurant',
                'items.product.images.media',
                'events',
            ])
            ->orderByDesc('created_at')
            ->paginate(20);

        return OrderResource::collection($orders);
    }

    public function show(Request $request, Order $order)
    {
        $user = $request->user();

        if($order->user_id !== $user->id) {
            abort(404);
        }

        $order->load([
            'restaurant',
            'items.product.images.media',
            'events',
        ]);

        return new OrderResource($order);
    }

    public function store(StoreOrderRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        $cart = Cart::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->with([
                'items.product',
                'restaurant',
            ])
            ->first();

        if(!$cart || $cart->items->isEmpty()) {
            return response()->json([
                'message' => 'Активная корзина пуста.',
            ], 422);
        }

        if(!$cart->restaurant_id) {
            return response()->json([
                'message' => 'Корзина не привязана к ресторану.',
            ], 422);
        }

        $total = 0;
        foreach ($cart->items as $item) {
            $total += (float)$item->unit_price_snapshot * (int)$item->quantity;
        }

        return DB::transaction(function () use ($cart, $user, $data, $total) {
            $order = Order::create([
                'user_id' => $user->id,
                'restaurant_id' => $cart->restaurant_id,
                'courier_id' => null,
                'status' => 'CREATED',
                'payment_status' => 'PENDING',
                'payment_method' => $data['payment_method'] ?? 'CASH',
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
                'event' => 'CREATED',
                'payload' => [
                    'cart_id' => $cart->id,
                ],
            ]);

            $cart->status = 'ORDERED';
            $cart->is_active = false;
            $cart->save();

            $order->load([
                'restaurant',
                'items.product.images.media',
                'events',
            ]);

            return (new OrderResource($order))
                ->response()
                ->setStatusCode(201);
        });
    }
}
