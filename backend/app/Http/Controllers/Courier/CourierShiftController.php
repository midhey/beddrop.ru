<?php

namespace App\Http\Controllers\Courier;

use App\Actions\Courier\EndCourierShift;
use App\Actions\Courier\StartCourierShift;
use App\Enums\CourierShiftStatus;
use App\Models\CourierShift;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CourierShiftController extends Controller
{
    public function start(Request $request, StartCourierShift $startCourierShift)
    {
        $shift = $startCourierShift($request->user());

        return response()->json([
            'shift' => $shift,
        ], 201);
    }

    public function end(Request $request, EndCourierShift $endCourierShift)
    {
        $shift = $endCourierShift($request->user());

        return response()->json([
            'shift' => $shift,
        ]);
    }

    public function current(Request $request)
    {
        $user = $request->user();

        $shift = CourierShift::query()
            ->where('courier_user_id', $user->id)
            ->where('status', CourierShiftStatus::OPEN->value)
            ->orderByDesc('started_at')
            ->first();

        return response()->json([
            'shift' => $shift,
        ]);
    }
}
