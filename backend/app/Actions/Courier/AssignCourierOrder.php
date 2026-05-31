<?php

namespace App\Actions\Courier;

use App\Actions\Order\StoreCourierApproachRoute;
use App\Actions\Order\TransitionOrderStatus;
use App\Enums\CourierProfileStatus;
use App\Enums\CourierShiftStatus;
use App\Enums\OrderStatus;
use App\Models\CourierProfile;
use App\Models\CourierShift;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AssignCourierOrder
{
    public function __construct(
        private readonly StoreCourierApproachRoute $storeCourierApproachRoute,
        private readonly TransitionOrderStatus $transitionOrderStatus,
    ) {}

    public function __invoke(User $user, Order $order): Order
    {
        $profile = $this->ensureOpenShift($user);

        return DB::transaction(function () use ($order, $profile) {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $lockedOrder->status !== OrderStatus::READY_FOR_PICKUP->value ||
                $lockedOrder->courier_id !== null
            ) {
                abort(422, 'Этот заказ нельзя взять в работу');
            }

            $lockedOrder = ($this->transitionOrderStatus)(
                $lockedOrder,
                OrderStatus::COURIER_ASSIGNED,
                [
                    'courier_user_id' => $profile->user_id,
                ],
                [
                    'courier_id' => $profile->user_id,
                ],
            );

            ($this->storeCourierApproachRoute)($lockedOrder, $profile);

            return $lockedOrder->load([
                'restaurant.address',
                'items.product',
                'deliveryAddress',
                'routeSegments',
            ]);
        });
    }

    private function ensureOpenShift(User $user): CourierProfile
    {
        $profile = $user->courierProfile;

        if (! $profile || $profile->status !== CourierProfileStatus::ACTIVE->value) {
            abort(403, 'Профиль курьера не найден или отключён.');
        }

        $hasOpenShift = CourierShift::query()
            ->where('courier_user_id', $profile->user_id)
            ->where('status', CourierShiftStatus::OPEN->value)
            ->exists();

        if (! $hasOpenShift) {
            abort(422, 'У вас нет открытой смены.');
        }

        return $profile;
    }
}
