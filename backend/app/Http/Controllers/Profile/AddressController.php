<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\StoreAddressRequest;
use App\Http\Requests\Profile\UpdateAddressRequest;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $addresses = Address::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'addresses' => $addresses,
        ]);
    }

    public function store(StoreAddressRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $address = Address::create([
            'user_id' => $user->id,
            'label' => $data['label'] ?? null,
            'line1' => $data['line1'],
            'line2' => $data['line2'] ?? null,
            'city' => $data['city'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'lat' => $data['lat'] ?? null,
            'lng' => $data['lng'] ?? null,
        ]);

        return response()->json([
            'address' => $address,
        ], 201);
    }

    public function update(UpdateAddressRequest $request, Address $address): JsonResponse
    {
        $user = $request->user();

        if($address->user_id !== $user->id) {
            abort(404);
        }

        $data = $request->validated();

        $address->fill([
            'label' => $data['label'] ?? $address->label,
            'line1' => $data['line1'] ?? $address->line1,
            'line2' => $data['line2'] ?? $address->line2,
            'city' => $data['city'] ?? $address->city,
            'postal_code' => $data['postal_code'] ?? $address->postal_code,
            'lat' => $data['lat'] ?? $address->lat,
            'lng' => $data['lng'] ?? $address->lng,
        ]);

        $address->save();

        return response()->json([
            'address' => $address,
        ]);
    }

    public function destroy(Request $request, Address $address): Response
    {
        $user = $request->user();

        if($address->user_id !== $user->id) {
            abort(404);
        }

        $address->delete();

        return response()->noContent();
    }
}
