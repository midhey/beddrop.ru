<?php

namespace App\Http\Controllers;

use App\Http\Requests\Restaurant\StoreRestaurantRequest;
use App\Http\Requests\Restaurant\UpdateRestaurantRequest;
use App\Models\Address;
use App\Models\Restaurant;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;

class RestaurantController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $restaurants = Restaurant::query()
            ->where('is_active', true)
            ->with(['address', 'logo'])
            ->paginate(20);

        return response()->json($restaurants);
    }

    public function show(Restaurant $restaurant)
    {
        $restaurant->load(['address', 'logo']);

        return response()->json([
            'restaurant' => $restaurant,
        ]);
    }

    public function store(StoreRestaurantRequest $request)
    {
        $this->authorize('create', Restaurant::class);

        $data = $request->validated();

        $addressData = $data['address'] ?? null;
        $address = null;

        if($addressData) {
            $address = Address::create([
                'user_id' => $request->user()->id,
                'label' => $addressData['label'] ?? null,
                'line1' => $addressData['line1'],
                'line2' => $addressData['line2'] ?? null,
                'city' => $addressData['city'] ?? null,
                'postal_code' => $addressData['postal_code'] ?? null,
                'lat' => $addressData['lat'] ?? null,
                'lng' => $addressData['lng'] ?? null,
            ]);
        }

        $restaurant = Restaurant::create([
            'name' => $data['name'],
            'slug' => $data['slug'] ?? Str::slug($data['name']) . '-' . uniqid(),
            'phone' => $data['phone'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'prep_time_min' => $data['prep_time_min'] ?? null,
            'prep_time_max' => $data['prep_time_max'] ?? null,
            'address_id' => $address?->id,
            'logo_media_id' => $data['logo_media_id'] ?? null,
        ]);

        $ownerId = $data['owner_id'] ?? $request->user()->id;

        $restaurant->users()->attach($ownerId, [
            'role' => 'OWNER',
        ]);

        return response()->json([
            'restaurant' => $restaurant->load(['address']),
        ], 201);
    }

    public function update(UpdateRestaurantRequest $request, Restaurant $restaurant)
    {
        $this->authorize('update', $restaurant);

        $data = $request->validated();

        $restaurant->fill([
            'name' => $data['name'] ?? $restaurant->name,
            'phone' => $data['phone'] ?? $restaurant->phone,
            'is_active' => $data['is_active'] ?? $restaurant->is_active,
            'prep_time_min' => $data['prep_time_min'] ?? $restaurant->prep_time_min,
            'prep_time_max' => $data['prep_time_max'] ?? $restaurant->prep_time_max,
            'logo_media_id' => $data['logo_media_id'] ?? $restaurant->logo_media_id,
        ]);

        if(!empty($data['slug']) && $data['slug'] !== $restaurant->slug) {
            $restaurant->slug = $data['slug'];
        }

        $restaurant->save();

        if(!empty($data['address'])) {
            $addressData = $data['address'];

            if($restaurant->address) {
                $restaurant->address->update($addressData);
            } else {
                $address = Address::create(
                    array_merge(
                        $addressData,
                        ['user_id' => $request->user()->id]
                    )
                );
                $restaurant->address_id = $address->id;
                $restaurant->save();
            }
        }

        return response()->json([
            'restaurant' => $restaurant->load('address'),
        ]);
    }

    public function users(Restaurant $restaurant)
    {
        $this->authorize('update', $restaurant);

        $users = $restaurant->users()
            ->select('users.id', 'users.email', 'users.name', 'users.phone')
            ->withPivot('role')
            ->get()
            ->map(function ($user) {
                return [
                    'id'    => $user->id,
                    'email' => $user->email,
                    'name'  => $user->name,
                    'phone' => $user->phone,
                    'role'  => $user->pivot->role,
                ];
            });

        return response()->json([
            'restaurant_id' => $restaurant->id,
            'users'         => $users,
        ]);
    }

    public function destroy(Restaurant $restaurant)
    {
        $this->authorize('delete', $restaurant);

        $restaurant->delete();

        return response()->json([
            'message' => 'Ресторан удалён',
        ]);
    }
}
