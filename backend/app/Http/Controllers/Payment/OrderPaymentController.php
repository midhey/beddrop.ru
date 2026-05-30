<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payments\OrderPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderPaymentController extends Controller
{
    public function store(Request $request, Order $order, OrderPaymentService $payments): JsonResponse
    {
        $this->ensureOwner($request, $order);

        return $this->response($payments->initiate($order));
    }

    public function sync(Request $request, Order $order, OrderPaymentService $payments): JsonResponse
    {
        $user = $request->user();

        if ($order->user_id !== $user->id && ! $user->is_admin) {
            abort(404);
        }

        $payment = $order->payment;

        if (! $payment) {
            abort(422, 'Для заказа еще нет платежа.');
        }

        return $this->response($payments->sync($payment));
    }

    public function status(Request $request, Order $order, OrderPaymentService $payments): JsonResponse
    {
        $this->ensureOwnerOrAdmin($request, $order);

        $payment = $order->payment;

        if (! $payment) {
            return response()->json([
                'result' => 'missing',
                'message' => 'Платеж еще не создан. Нажмите «Оплатить», чтобы перейти к форме YooKassa.',
                'order' => [
                    'id' => $order->id,
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                ],
            ]);
        }

        $response = $this->response($payments->sync($payment))->getData(true);
        $response['result'] = 'synced';
        $response['message'] = $this->paymentStatusMessage($response['order']['payment_status'] ?? $order->payment_status);

        return response()->json($response);
    }

    private function ensureOwner(Request $request, Order $order): void
    {
        if ($order->user_id !== $request->user()->id) {
            abort(404);
        }
    }

    private function ensureOwnerOrAdmin(Request $request, Order $order): void
    {
        $user = $request->user();

        if ($order->user_id !== $user->id && ! $user->is_admin) {
            abort(404);
        }
    }

    private function paymentStatusMessage(string $paymentStatus): string
    {
        return match ($paymentStatus) {
            'PAID' => 'Оплата подтверждена.',
            'FAILED' => 'Платеж не прошел. Попробуйте оплатить заказ еще раз.',
            'AUTHORIZED' => 'Оплата авторизована, ожидаем подтверждение.',
            default => 'Оплата пока не подтверждена. Проверьте статус чуть позже.',
        };
    }

    private function response(Payment $payment): JsonResponse
    {
        return response()->json([
            'payment' => [
                'id' => $payment->id,
                'provider' => $payment->provider,
                'provider_payment_id' => $payment->provider_payment_id,
                'provider_status' => $payment->provider_status,
                'confirmation_url' => $payment->confirmation_url,
                'amount_value' => $payment->amount_value,
                'currency' => $payment->currency,
                'synced_at' => $payment->synced_at,
                'confirmed_at' => $payment->confirmed_at,
                'failed_at' => $payment->failed_at,
            ],
            'order' => [
                'id' => $payment->order?->id,
                'status' => $payment->order?->status,
                'payment_status' => $payment->order?->payment_status,
            ],
        ]);
    }
}
