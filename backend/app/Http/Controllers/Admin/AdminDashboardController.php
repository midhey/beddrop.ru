<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\CourierProfile;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        [$from, $to] = $this->period($request);

        $cancelledStatuses = [
            OrderStatus::CANCELED_BY_USER->value,
            OrderStatus::CANCELED_BY_RESTAURANT->value,
        ];

        $periodOrders = $this->ordersForPeriod($from, $to);
        $deliveredOrders = $this->deliveredOrdersForPeriod($from, $to);
        $deliveredOrderModels = (clone $deliveredOrders)
            ->get(['id', 'delivery_price_snapshot', 'courier_fee', 'logistics_snapshot_json']);

        $deliveryRevenue = $this->deliveryRevenue($deliveredOrderModels);
        $courierPayouts = $this->courierPayouts($deliveredOrderModels);
        $serviceFeeRevenue = $this->serviceFeeRevenue($deliveredOrderModels);
        $serviceCommissionRevenue = $this->serviceCommissionRevenue($deliveredOrderModels);

        $daily = $this->deliveredOrdersForPeriod($from, $to)
            ->selectRaw('DATE(created_at) as day')
            ->selectRaw('COUNT(*) as orders_count')
            ->selectRaw('COALESCE(SUM(total_price), 0) as revenue')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($row) => [
                'day' => $row->day,
                'orders_count' => (int) $row->orders_count,
                'revenue' => (float) $row->revenue,
            ])
            ->values();

        return response()->json([
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'metrics' => [
                'orders_total' => (clone $periodOrders)->count(),
                'orders_delivered' => $deliveredOrders->count(),
                'orders_cancelled' => (clone $periodOrders)->whereIn('status', $cancelledStatuses)->count(),
                'gmv' => (float) (clone $deliveredOrders)->sum('total_price'),
                'delivery_revenue' => $deliveryRevenue,
                'courier_payouts' => $courierPayouts,
                'service_fee_revenue' => $serviceFeeRevenue,
                'service_commission_revenue' => $serviceCommissionRevenue,
                'delivery_margin' => round($deliveryRevenue - $courierPayouts, 2),
                'service_revenue_total' => round($serviceFeeRevenue + $serviceCommissionRevenue, 2),
                'average_check' => (float) (clone $deliveredOrders)->avg('total_price'),
                'average_delivery_minutes' => (float) ((clone $deliveredOrders)->whereNotNull('delivery_duration_seconds')->avg('delivery_duration_seconds') ?? 0) / 60,
                'active_restaurants' => Restaurant::query()->where('is_active', true)->count(),
                'active_couriers' => CourierProfile::query()->where('status', 'ACTIVE')->count(),
                'users_total' => User::query()->count(),
            ],
            'daily' => $daily,
            'top_restaurants' => $this->topRestaurants($from, $to),
            'top_couriers' => $this->topCouriers($from, $to),
            'cancellations' => $this->cancellations($from, $to, $cancelledStatuses),
        ]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function period(Request $request): array
    {
        $from = $request->date('from')?->startOfDay() ?? now()->subDays(30)->startOfDay();
        $to = $request->date('to')?->endOfDay() ?? now()->endOfDay();

        return [$from, $to];
    }

    private function ordersForPeriod(Carbon $from, Carbon $to): Builder
    {
        return Order::query()
            ->whereBetween('created_at', [$from, $to]);
    }

    private function deliveredOrdersForPeriod(Carbon $from, Carbon $to): Builder
    {
        return $this->ordersForPeriod($from, $to)
            ->where('status', OrderStatus::DELIVERED->value);
    }

    private function topRestaurants(Carbon $from, Carbon $to): array
    {
        return $this->deliveredOrdersForPeriod($from, $to)
            ->select('restaurant_id')
            ->selectRaw('COUNT(*) as orders_count')
            ->selectRaw('COALESCE(SUM(total_price), 0) as revenue')
            ->with('restaurant:id,name,slug')
            ->groupBy('restaurant_id')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get()
            ->map(fn (Order $order) => [
                'restaurant' => $order->restaurant,
                'orders_count' => (int) $order->orders_count,
                'revenue' => (float) $order->revenue,
            ])
            ->all();
    }

    private function topCouriers(Carbon $from, Carbon $to): array
    {
        return $this->deliveredOrdersForPeriod($from, $to)
            ->select('courier_id')
            ->selectRaw('COUNT(*) as orders_count')
            ->selectRaw('COALESCE(SUM(courier_fee), 0) as payouts')
            ->whereNotNull('courier_id')
            ->with('courier.user:id,name,email,phone')
            ->groupBy('courier_id')
            ->orderByDesc('orders_count')
            ->limit(10)
            ->get()
            ->map(fn (Order $order) => [
                'courier' => $order->courier,
                'orders_count' => (int) $order->orders_count,
                'payouts' => (float) $order->payouts,
            ])
            ->all();
    }

    private function cancellations(Carbon $from, Carbon $to, array $statuses): array
    {
        return $this->ordersForPeriod($from, $to)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->whereIn('status', $statuses)
            ->groupBy('status')
            ->get()
            ->map(fn ($row) => [
                'status' => $row->status,
                'count' => (int) $row->count,
            ])
            ->all();
    }

    private function deliveryRevenue(Collection $orders): float
    {
        return (float) $orders->sum(fn (Order $order) => (float) ($order->delivery_price_snapshot ?? 0));
    }

    private function courierPayouts(Collection $orders): float
    {
        return (float) $orders->sum(fn (Order $order) => (float) ($order->courier_fee ?? 0));
    }

    private function serviceFeeRevenue(Collection $orders): float
    {
        return (float) $orders->sum(fn (Order $order) => (float) ($order->logistics_snapshot_json['price']['service'] ?? 0));
    }

    private function serviceCommissionRevenue(Collection $orders): float
    {
        return (float) $orders->sum(function (Order $order) {
            $deliveryPrice = (float) ($order->delivery_price_snapshot ?? 0);
            $commissionPercent = (float) ($order->logistics_snapshot_json['settings']['delivery.service_commission_percent'] ?? 0);

            return round($deliveryPrice * $commissionPercent / 100, 2);
        });
    }
}
