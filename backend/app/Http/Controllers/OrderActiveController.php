<?php

namespace App\Http\Controllers;

use App\Actions\Order\FindActiveOrder;
use App\Http\Resources\ActiveOrderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderActiveController extends Controller
{
    public function __invoke(Request $request, FindActiveOrder $findActiveOrder): JsonResponse
    {
        $order = $findActiveOrder($request->user()->id);

        return response()->json([
            'order' => $order ? new ActiveOrderResource($order) : null,
        ]);
    }
}
