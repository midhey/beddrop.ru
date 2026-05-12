<?php

namespace App\Http\Controllers\Restaurant;

use App\Enums\RestaurantStaffRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Restaurant\StoreRestaurantRequest;
use App\Http\Requests\Restaurant\UpdateRestaurantRequest;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use App\Models\Restaurant;
use App\Models\User;
use App\Support\PublicDataCache;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class RestaurantController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $validated = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:product_categories,id'],
        ]);

        $payload = PublicDataCache::remember(
            PublicDataCache::RESTAURANTS,
            [
                'category_id' => $validated['category_id'] ?? null,
                'page' => $request->integer('page', 1),
            ],
            function () use ($validated) {
                $restaurants = Restaurant::query()
                    ->where('is_active', true)
                    ->with(['address', 'logo']);

                if (array_key_exists('category_id', $validated) && $validated['category_id'] !== null) {
                    $restaurants->whereHas('products', function ($query) use ($validated) {
                        $query
                            ->where('category_id', $validated['category_id'])
                            ->where('is_active', true);
                    });
                }

                return $restaurants->paginate(20)->toArray();
            },
        );

        return response()->json($this->withFreshAvailabilityForPaginatedPayload($payload));
    }

    public function show(Request $request, Restaurant $restaurant)
    {
        $this->authorize('view', $restaurant);
        $user = $request->user('api');

        if ($user === null && $restaurant->is_active) {
            $payload = PublicDataCache::remember(
                PublicDataCache::RESTAURANT_DETAILS,
                ['slug' => $restaurant->slug],
                function () use ($restaurant) {
                    $restaurant->load(['address', 'logo']);

                    return [
                        'restaurant' => $this->serializeRestaurant($restaurant, null),
                    ];
                },
            );

            return response()->json($this->withFreshAvailabilityForRestaurantPayload($payload));
        }

        $restaurant->load(['address', 'logo']);

        return response()->json([
            'restaurant' => $this->serializeRestaurant($restaurant, $user),
        ]);
    }

    public function store(StoreRestaurantRequest $request)
    {
        $this->authorize('create', Restaurant::class);

        $data = $request->validated();

        $addressData = $data['address'] ?? null;
        $address = null;

        if($addressData) {
            $address = Address::create($this->addressPayload($addressData));
        }

        $restaurant = Restaurant::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'slug' => $data['slug'] ?? null,
            'phone' => $data['phone'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'accepts_orders' => $data['accepts_orders'] ?? true,
            'timezone' => $data['timezone'] ?? 'Europe/Moscow',
            'opens_at' => $data['opens_at'] ?? null,
            'closes_at' => $data['closes_at'] ?? null,
            'closed_reason' => $data['closed_reason'] ?? null,
            'prep_time_min' => $data['prep_time_min'] ?? null,
            'prep_time_max' => $data['prep_time_max'] ?? null,
            'address_id' => $address?->id,
            'logo_media_id' => $data['logo_media_id'] ?? null,
        ]);

        $user = $request->user();
        $ownerId = ($user->is_admin && !empty($data['owner_id']))
            ? $data['owner_id']
            : $user->id;

        $restaurant->users()->attach($ownerId, [
            'role' => RestaurantStaffRole::OWNER->value,
        ]);

        return response()->json([
            'restaurant' => $this->serializeRestaurant(
                $restaurant->load(['address', 'logo']),
                $request->user(),
            ),
        ], 201);
    }

    public function update(UpdateRestaurantRequest $request, Restaurant $restaurant)
    {
        $this->authorize('update', $restaurant);

        $data = $request->validated();

        $restaurant->fill([
            'name' => $data['name'] ?? $restaurant->name,
            'description' => array_key_exists('description', $data) ? $data['description'] : $restaurant->description,
            'phone' => array_key_exists('phone', $data) ? $data['phone'] : $restaurant->phone,
            'is_active' => $data['is_active'] ?? $restaurant->is_active,
            'accepts_orders' => $data['accepts_orders'] ?? $restaurant->accepts_orders,
            'timezone' => $data['timezone'] ?? $restaurant->timezone,
            'opens_at' => array_key_exists('opens_at', $data) ? $data['opens_at'] : $restaurant->opens_at,
            'closes_at' => array_key_exists('closes_at', $data) ? $data['closes_at'] : $restaurant->closes_at,
            'closed_reason' => array_key_exists('closed_reason', $data) ? $data['closed_reason'] : $restaurant->closed_reason,
            'prep_time_min' => array_key_exists('prep_time_min', $data) ? $data['prep_time_min'] : $restaurant->prep_time_min,
            'prep_time_max' => array_key_exists('prep_time_max', $data) ? $data['prep_time_max'] : $restaurant->prep_time_max,
            'logo_media_id' => array_key_exists('logo_media_id', $data) ? $data['logo_media_id'] : $restaurant->logo_media_id,
        ]);

        if(!empty($data['slug']) && $data['slug'] !== $restaurant->slug) {
            $restaurant->slug = $data['slug'];
        }

        $restaurant->save();

        if(!empty($data['address'])) {
            $addressData = $data['address'];

            if($restaurant->address) {
                $restaurant->address->update($this->addressPayload($addressData));
            } else {
                $address = Address::create($this->addressPayload($addressData));
                $restaurant->address_id = $address->id;
                $restaurant->save();
            }
        }

        return response()->json([
            'restaurant' => $this->serializeRestaurant(
                $restaurant->load(['address', 'logo']),
                $request->user(),
            ),
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

    public function my(Request $request)
    {
        $user = $request->user();

        $restaurants = Restaurant::query()
            ->whereHas('users', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->with(['address', 'logo'])
            ->get();

        return response()->json([
            'restaurants' => $restaurants->map(fn (Restaurant $restaurant) => $this->serializeRestaurant($restaurant, $user)),
        ]);
    }

    private function serializeRestaurant(Restaurant $restaurant, ?User $user): array
    {
        $currentUserRole = null;

        if ($user) {
            $restaurant->loadMissing(['users' => fn ($query) => $query->select('users.id')]);
            $membership = $restaurant->users->firstWhere('id', $user->id);
            $currentUserRole = $membership?->pivot?->role;
        }

        return [
            'id' => $restaurant->id,
            'name' => $restaurant->name,
            'description' => $restaurant->description,
            'slug' => $restaurant->slug,
            'phone' => $restaurant->phone,
            'is_active' => (bool) $restaurant->is_active,
            'accepts_orders' => (bool) ($restaurant->accepts_orders ?? true),
            'timezone' => $restaurant->timezone ?: 'Europe/Moscow',
            'opens_at' => $restaurant->availability()['opens_at'],
            'closes_at' => $restaurant->availability()['closes_at'],
            'closed_reason' => $restaurant->closed_reason,
            'availability' => $restaurant->availability(),
            'prep_time_min' => $restaurant->prep_time_min,
            'prep_time_max' => $restaurant->prep_time_max,
            'prep_time_avg_minutes' => $restaurant->prepTimeAverageMinutes(),
            'address_id' => $restaurant->address_id,
            'logo_media_id' => $restaurant->logo_media_id,
            'current_user_role' => $currentUserRole,
            'address' => $restaurant->address
                ? (new AddressResource($restaurant->address))->resolve()
                : null,
            'logo' => $restaurant->logo ? [
                'id' => $restaurant->logo->id,
                'url' => $restaurant->logo->url,
            ] : null,
            'created_at' => $restaurant->created_at,
            'updated_at' => $restaurant->updated_at,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function withFreshAvailabilityForPaginatedPayload(array $payload): array
    {
        $payload['data'] = collect($payload['data'] ?? [])
            ->map(fn (array $restaurant) => $this->withFreshAvailability($restaurant))
            ->all();

        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function withFreshAvailabilityForRestaurantPayload(array $payload): array
    {
        if (isset($payload['restaurant']) && is_array($payload['restaurant'])) {
            $payload['restaurant'] = $this->withFreshAvailability($payload['restaurant']);
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $restaurant
     * @return array<string, mixed>
     */
    private function withFreshAvailability(array $restaurant): array
    {
        $model = new Restaurant($restaurant);
        $model->exists = true;

        $restaurant['accepts_orders'] = (bool) ($restaurant['accepts_orders'] ?? true);
        $restaurant['timezone'] = $restaurant['timezone'] ?? 'Europe/Moscow';
        $restaurant['opens_at'] = $model->availability()['opens_at'];
        $restaurant['closes_at'] = $model->availability()['closes_at'];
        $restaurant['closed_reason'] = $restaurant['closed_reason'] ?? null;
        $restaurant['availability'] = $model->availability();

        return $restaurant;
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
