<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Services\Payments\OrderPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class YooKassaWebhookController extends Controller
{
    public function __invoke(Request $request, OrderPaymentService $payments): JsonResponse
    {
        $providerPaymentId = $request->input('object.id');

        if (! is_string($providerPaymentId) || $providerPaymentId === '') {
            return response()->json(['message' => 'Ignored webhook without payment id.']);
        }

        $payments->syncByProviderPaymentId($providerPaymentId);

        return response()->json(['message' => 'ok']);
    }
}
