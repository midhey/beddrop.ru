<?php

namespace App\Http\Controllers\Courier;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CourierOrderController extends Controller
{
    private const BASE_COURIER_FEE = 150.00;

    private function ensureCourier(Request $request)
    {
        $profile = $request->user()->courierProfile;

        if(!$profile || $profile->status !== 'ACTIVE') {
            abort(403, 'Профиль курьера не найден или отключён.');
        }

        return $profile;
    }

    public function available(Request $request)
    {
        $this->ensureCourier($request);

        $orders = Order::query()
            ->where('status', 'ACCEPTED_BY_RESTAURANT')
            ->whereNull('courier_id')
            ->with(['restaurant', 'items.product'])
            ->orderBy('created_at')
            ->paginate(20);

        return OrderResource::collection($orders);
    }

    public function active(Request $request)
    {
        $profile = $this->ensureCourier($request);

        $orders = Order::query()
            ->where('courier_id', $profile->user_id)
            ->whereIn('status', ['COURIER_ASSIGNED', 'PICKED_UP'])
            ->with(['restaurant', 'items.product'])
            ->orderBy('created_at')
            ->paginate(20);

        return OrderResource::collection($orders);
    }

    public function history(Request $request)
    {
        $profile = $this->ensureCourier($request);

        $orders = Order::query()
            ->where('courier_id', $profile->user_id)
            ->where('status', 'DELIVERED')
            ->with(['restaurant', 'items.product'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return OrderResource::collection($orders);
    }

    public function assign(Request $request, Order $order)
    {
        $profile = $this->ensureCourier($request);

        if($order->status !== 'ACCEPTED_BY_RESTAURANT' || $order->courier_id !== null) {
            return response()->json([
                'message' => 'Этот заказ нельзя взять в работу',
            ], 422);
        }

        return DB::transaction(function () use ($order, $profile) {
            $order->courier_id = $profile->user_id;
            $order->status = 'COURIER_ASSIGNED';
            $order->save();

            OrderEvent::create([
                'order_id' => $order->id,
                'event' => 'COURIER_ASSIGNED',
                'payload' => [
                    'courier_user_id' => $profile->user_id,
                ],
            ]);

            $order->load(['restaurant', 'items.product']);

            return new OrderResource($order);
        });
    }

    public function pickedUp(Request $request, Order $order)
    {
        $profile = $this->ensureCourier($request);

        if($order->courier_id !== $profile->user_id) {
            abort(403, 'Вы не назначены на этот заказ.');
        }

        if(!in_array($order->status, ['COURIER_ASSIGNED'], true)) {
            return response()->json([
                'message' => 'Нельзя перевести заказ в статус PICKED_UP',
            ], 422);
        }

        return DB::transaction(function () use ($order, $profile) {
            $order->status = 'PICKED_UP';
            $order->save();

            OrderEvent::create([
                'order_id' => $order->id,
                'event' => 'PICKED_UP',
                'payload' => [
                    'courier_user_id' => $profile->user_id,
                ],
            ]);

            $order->load(['restaurant', 'items.product']);

            return new OrderResource($order);
        });
    }

    public function delivered(Request $request, Order $order)
    {
        $profile = $this->ensureCourier($request);

        if($order->courier_id !== $profile->user_id) {
            abort(403, 'Вы не назначены на этот заказ.');
        }

        if(!in_array($order->status, ['PICKED_UP'], true)) {
            return response()->json([
                'message' => 'Нельзя перевести заказ в статус DELIVERED',
            ], 422);
        }

        return DB::transaction(function () use ($order, $profile) {
            $order->status = 'DELIVERED';
            $order->courier_fee = self::BASE_COURIER_FEE;
            $order->save();

            OrderEvent::create([
                'order_id' => $order->id,
                'event' => 'DELIVERED',
                'payload' => [
                    'courier_user_id' => $profile->user_id,
                    'courier_fee' => $order->courier_fee,
                ],
            ]);

            $order->load(['restaurant', 'items.product', 'events']);

            return new OrderResource($order);
        });
    }
}
