<?php

namespace App\Http\Controllers\Restaurant;

use App\Actions\Restaurant\AcceptRestaurantOrder;
use App\Actions\Restaurant\CancelRestaurantOrder;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

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

    public function accept(
        Restaurant $restaurant,
        Order $order,
        Request $request,
        AcceptRestaurantOrder $acceptRestaurantOrder
    )
    {
        $this->authorize('manageOrders', $restaurant);

        if($order->restaurant_id !== $restaurant->id) {
            abort(404);
        }

        return new OrderResource($acceptRestaurantOrder($order, $request->user()));
    }

    public function cancel(
        Restaurant $restaurant,
        Order $order,
        Request $request,
        CancelRestaurantOrder $cancelRestaurantOrder
    )
    {
        $this->authorize('manageOrders', $restaurant);

        if($order->restaurant_id !== $restaurant->id) {
            abort(404);
        }

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        return new OrderResource($cancelRestaurantOrder($order, $request->user(), $data));
    }
}
