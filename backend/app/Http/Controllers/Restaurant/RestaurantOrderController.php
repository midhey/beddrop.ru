<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\OrderEvent;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RestaurantOrderController extends Controller
{
    use AuthorizesRequests;

    public function index(Restaurant $restaurant, Request $request)
    {
        $this->authorize('manageOrders', $restaurant);

        $perPage = min($request->integer('per_page', 20), 100);

        $query = Order::query()
            ->where('restaurant_id', $restaurant->id)
            ->with(['user', 'items.product'])
            ->orderByDesc('created_at');

        if($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $orders = $query->paginate($perPage);

        return OrderResource::collection($orders);
    }

    public function show(Restaurant $restaurant, Order $order)
    {
        $this->authorize('manageOrders', $restaurant);

        if($order->restaurant_id !== $restaurant->id) {
            abort(404);
        }

        $order->load(['user', 'items.product', 'events']);

        return new OrderResource($order);
    }

    public function accept(Restaurant $restaurant, Order $order, Request $request)
    {
        $this->authorize('manageOrders', $restaurant);

        if($order->restaurant_id !== $restaurant->id) {
            abort(404);
        }

        if($order->status !== 'CREATED') {
            return response()->json([
                'message' => 'Этот заказ уже обработан и не может быть принят.',
            ], 422);
        }

        return DB::transaction(function () use ($order, $request) {
            $order->status = 'ACCEPTED_BY_RESTAURANT';
            $order->save();

            OrderEvent::create([
                'order_id' => $order->id,
                'event' => 'ACCEPTED_BY_RESTAURANT',
                'payload' => [
                    'by_user_id' => $request->user()->id,
                ],
            ]);

            $order->load(['user', 'items.product', 'events']);

            return new OrderResource($order);
        });
    }

    public function cancel(Restaurant $restaurant, Order $order, Request $request)
    {
        $this->authorize('manageOrders', $restaurant);

        if($order->restaurant_id !== $restaurant->id) {
            abort(404);
        }

        if(in_array($order->status, ['DELIVERED', 'CANCELED_BY_USER', 'CANCELED_BY_RESTAURANT'], true)) {
            return response()->json([
                'message' => 'Этот заказ уже завершён и не может быть отменён рестораном.',
            ], 422);
        }

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        return DB::transaction(function () use ($order, $request, $data) {
            $order->status = 'CANCELED_BY_RESTAURANT';
            $order->save();

            OrderEvent::create([
                'order_id' => $order->id,
                'event' => 'CANCELED_BY_RESTAURANT',
                'payload' => [
                    'by_user_id' => $request->user()->id,
                    'reason' => $data['reason'] ?? null,
                ],
            ]);

            $order->load(['user', 'items.product', 'events']);

            return new OrderResource($order);
        });
    }
}
