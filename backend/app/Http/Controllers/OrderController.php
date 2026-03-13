<?php

namespace App\Http\Controllers;

use App\Actions\Order\CreateOrder;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;

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

    public function store(StoreOrderRequest $request, CreateOrder $createOrder)
    {
        $data = $request->validated();
        $order = $createOrder($request->user(), $data);

        return (new OrderResource($order))
            ->response()
            ->setStatusCode(201);
    }
}
