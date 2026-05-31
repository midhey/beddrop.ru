<?php

namespace App\Http\Controllers\Courier;

use App\Enums\CourierProfileStatus;
use App\Enums\CourierShiftStatus;
use App\Http\Controllers\Controller;
use App\Models\CourierLocation;
use App\Models\CourierShift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourierLocationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $profile = $request->user()->courierProfile;

        if (!$profile || $profile->status !== CourierProfileStatus::ACTIVE->value) {
            abort(403, 'Профиль курьера не найден или отключён.');
        }

        $hasOpenShift = CourierShift::query()
            ->where('courier_user_id', $profile->user_id)
            ->where('status', CourierShiftStatus::OPEN->value)
            ->exists();

        if (!$hasOpenShift) {
            abort(422, 'У вас нет открытой смены.');
        }

        $data = $request->validate([
            'lat' => ['required', 'numeric', 'min:-90', 'max:90'],
            'lng' => ['required', 'numeric', 'min:-180', 'max:180'],
            'accuracy' => ['nullable', 'numeric'],
            'heading' => ['nullable', 'numeric'],
            'speed' => ['nullable', 'numeric'],
            'recorded_at' => ['nullable', 'date'],
        ]);

        $location = CourierLocation::create([
            'courier_user_id' => $profile->user_id,
            'lat' => $data['lat'],
            'lng' => $data['lng'],
            'accuracy' => $data['accuracy'] ?? null,
            'heading' => $data['heading'] ?? null,
            'speed' => $data['speed'] ?? null,
            'recorded_at' => $data['recorded_at'] ?? now(),
        ]);

        return response()->json([
            'location' => $location,
        ], 201);
    }
}
