<?php

namespace App\Http\Controllers\Courier;

use App\Enums\CourierProfileStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Logistics\CourierPayoutCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CourierEarningsController extends Controller
{
    /**
     * Courier earnings summary.
     *
     * Returns delivery and payout totals for the authenticated active courier.
     * Period buckets use delivered order `updated_at` timestamps.
     *
     * @authenticated
     *
     * @response 200 {
     *   "today": {"deliveries_count": 2, "earnings_sum": "300.00", "total_orders_sum": "1490.00"},
     *   "week": {"deliveries_count": 5, "earnings_sum": "750.00", "total_orders_sum": "4120.00"},
     *   "total": {"deliveries_count": 12, "earnings_sum": "1800.00", "total_orders_sum": "10880.00"}
     * }
     * @response 403 {"message": "Профиль курьера не найден или отключён."}
     */
    public function __invoke(Request $request, CourierPayoutCalculator $payouts): JsonResponse
    {
        $profile = $request->user()->courierProfile;

        if (! $profile || $profile->status !== CourierProfileStatus::ACTIVE->value) {
            abort(403, 'Профиль курьера не найден или отключён.');
        }

        return response()->json([
            'today' => $this->bucket($profile->user_id, $payouts, now()->startOfDay()),
            'week' => $this->bucket($profile->user_id, $payouts, now()->startOfWeek()),
            'total' => $this->bucket($profile->user_id, $payouts),
        ]);
    }

    private function bucket(int $courierUserId, CourierPayoutCalculator $payouts, ?Carbon $since = null): array
    {
        $query = Order::query()
            ->where('courier_id', $courierUserId)
            ->where('status', OrderStatus::DELIVERED->value);

        if ($since !== null) {
            $query->where('updated_at', '>=', $since);
        }

        $orders = $query->get();

        $earnings = 0.0;
        $totalOrders = 0.0;

        foreach ($orders as $order) {
            $storedFee = $order->courier_fee !== null ? (float) $order->courier_fee : 0.0;
            $earnings += $storedFee > 0 ? $storedFee : $payouts->calculate($order);
            $totalOrders += (float) $order->total_price;
        }

        return [
            'deliveries_count' => $orders->count(),
            'earnings_sum' => number_format(round($earnings, 2), 2, '.', ''),
            'total_orders_sum' => number_format(round($totalOrders, 2), 2, '.', ''),
        ];
    }
}
