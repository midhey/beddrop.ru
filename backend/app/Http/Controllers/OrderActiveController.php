<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderActiveController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $order = Order::query()
            ->where('user_id', $request->user()->id)
            ->whereNotIn('status', [
                OrderStatus::DELIVERED->value,
                OrderStatus::CANCELED_BY_USER->value,
                OrderStatus::CANCELED_BY_RESTAURANT->value,
            ])
            ->with(['restaurant:id,name,slug'])
            ->withSum('items as items_count', 'quantity')
            ->orderByDesc('created_at')
            ->first();

        return response()->json([
            'order' => $order ? [
                'id' => $order->id,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'payment_method' => $order->payment_method,
                'total_price' => $order->total_price,
                'courier_fee' => $order->courier_fee,
                'delivery_distance_meters' => $order->delivery_distance_meters,
                'delivery_duration_seconds' => $order->delivery_duration_seconds,
                'delivery_price_snapshot' => $order->delivery_price_snapshot,
                'estimated_pickup_at' => $order->estimated_pickup_at,
                'estimated_delivery_at' => $order->estimated_delivery_at,
                'restaurant' => $order->restaurant ? [
                    'id' => $order->restaurant->id,
                    'name' => $order->restaurant->name,
                    'slug' => $order->restaurant->slug,
                ] : null,
                'items_count' => (int) ($order->items_count ?? 0),
                'created_at' => $order->created_at,
                'updated_at' => $order->updated_at,
            ] : null,
        ]);
    }
}
