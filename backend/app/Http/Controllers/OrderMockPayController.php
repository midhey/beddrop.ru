<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;

class OrderMockPayController extends Controller
{
    public function __invoke(Order $order): RedirectResponse
    {
        $order->update([
            'payment_status' => PaymentStatus::PAID->value,
        ]);

        return redirect(config('app.frontend_url') . '/orders/' . $order->id);
    }
}
