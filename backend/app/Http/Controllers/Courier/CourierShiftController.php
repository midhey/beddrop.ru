<?php

namespace App\Http\Controllers\Courier;

use App\Models\CourierShift;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CourierShiftController extends Controller
{
    private function ensureCourier(Request $request)
    {
        $profile = $request->user()->courierProfile;

        if(!$profile || $profile->status !== 'ACTIVE') {
            abort(403, 'Профиль курьера не найден или отключён.');
        }

        return $profile;
    }

    public function start(Request $request)
    {
        $profile = $this->ensureCourier($request);

        $openShift = CourierShift::where('courier_user_id', $profile->user_id)
            ->where('status', 'OPEN')
            ->first();

        if($openShift) {
            return response()->json([
                'message' => 'У вас уже есть открытая смена',
                'shift' => $openShift,
            ], 422);
        }

        $shift = CourierShift::create([
            'courier_user_id' => $profile->user_id,
            'started_at' => now(),
            'status' => 'OPEN',
        ]);

        return response()->json([
            'shift' => $shift,
        ], 201);
    }

    public function end(Request $request)
    {
        $profile = $this->ensureCourier($request);

        $shift = CourierShift::where('courier_user_id', $profile->user_id)
            ->where('status', 'OPEN')
            ->first();

        if(!$shift) {
            return response()->json([
                'message' => 'Нет открытой смены',
            ], 422);
        }

        $shift->ended_at = now();
        $shift->status = 'CLOSED';
        $shift->save();

        return response()->json([
            'shift' => $shift,
        ]);
    }
}
