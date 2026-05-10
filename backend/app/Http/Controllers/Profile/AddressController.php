<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\StoreAddressRequest;
use App\Http\Requests\Profile\UpdateAddressRequest;
use App\Http\Resources\AddressResource;
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
            'addresses' => AddressResource::collection($addresses)->resolve(),
        ]);
    }

    public function store(StoreAddressRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $address = Address::create($this->addressPayload($data, $user->id));

        return response()->json([
            'address' => new AddressResource($address),
        ], 201);
    }

    public function update(UpdateAddressRequest $request, Address $address): JsonResponse
    {
        $user = $request->user();

        if($address->user_id !== $user->id) {
            abort(404);
        }

        $data = $request->validated();

        $address->fill($this->addressPayload($data));

        $address->save();

        return response()->json([
            'address' => new AddressResource($address),
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

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function addressPayload(array $data, ?int $userId = null): array
    {
        if ($userId !== null) {
            $data['user_id'] = $userId;
        }

        $data['line1'] ??= $data['value'] ?? $data['unrestricted_value'] ?? null;
        $data['geo_source'] ??= isset($data['raw_dadata_json']) ? 'dadata' : 'manual';

        return $data;
    }
}
