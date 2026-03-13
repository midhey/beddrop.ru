<?php

namespace App\Http\Controllers\Courier;

use App\Enums\CourierProfileStatus;
use App\Enums\CourierVehicle;
use App\Models\CourierProfile;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rule;

class CourierProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $profile = $user->courierProfile;

        if(!$profile) {
            return response()->json([
                'message' => 'Профиль курьера не найден',
            ], 404);
        }

        return response()->json([
            'profile' => [
                'user_id' => $profile->user_id,
                'status' => $profile->status,
                'vehicle' => $profile->vehicle,
                'rating' => $profile->rating,
            ],
        ]);
    }

    public function upsert(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'vehicle' => ['nullable', Rule::enum(CourierVehicle::class)],
        ]);

        $profile = CourierProfile::updateOrCreate(
            ['user_id' => $user->id],
            array_merge($data, ['status' => CourierProfileStatus::ACTIVE->value])
        );

        return response()->json([
            'profile' => $profile,
        ], 200);
    }
}
