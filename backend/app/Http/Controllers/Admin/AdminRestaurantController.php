<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RestaurantStaffRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Restaurant\UpdateRestaurantRequest;
use App\Http\Resources\RestaurantResource;
use App\Models\Address;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\Admin\AdminActionLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminRestaurantController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->integer('per_page', 20), 100);
        $query = Restaurant::query()
            ->with(['address', 'logo'])
            ->withCount(['orders', 'products', 'users'])
            ->orderByDesc('created_at');

        if ($search = trim((string) $request->get('search'))) {
            $query->where(function ($query) use ($search) {
                $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        foreach (['is_active', 'accepts_orders'] as $flag) {
            if ($request->has($flag) && $request->get($flag) !== '') {
                $query->where($flag, $request->boolean($flag));
            }
        }

        return response()->json($query->paginate($perPage));
    }

    public function show(Restaurant $restaurant): JsonResponse
    {
        $restaurant->load(['address', 'logo', 'users:id,name,email,phone']);
        $restaurant->loadCount(['orders', 'products', 'users']);

        return response()->json([
            'restaurant' => (new RestaurantResource($restaurant))->resolve(),
            'staff' => $restaurant->users->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->pivot->role,
            ]),
        ]);
    }

    public function update(UpdateRestaurantRequest $request, Restaurant $restaurant, AdminActionLogger $logger): JsonResponse
    {
        $data = $request->validated();
        $before = $restaurant->toArray();

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

        if (! empty($data['slug']) && $data['slug'] !== $restaurant->slug) {
            $restaurant->slug = $data['slug'];
        }

        $restaurant->save();

        if (! empty($data['address'])) {
            $addressData = $data['address'];

            if ($restaurant->address) {
                $restaurant->address->update($addressData);
            } else {
                $address = Address::create($addressData);
                $restaurant->address_id = $address->id;
                $restaurant->save();
            }
        }

        if (array_key_exists('owner_id', $data) && $data['owner_id']) {
            $owner = User::findOrFail($data['owner_id']);
            $restaurant->users()->syncWithoutDetaching([
                $owner->id => ['role' => RestaurantStaffRole::OWNER->value],
            ]);
        }

        $logger->log(
            $request->user(),
            'admin.restaurant.update',
            $restaurant,
            before: $before,
            after: $restaurant->fresh()->toArray(),
        );

        return response()->json([
            'restaurant' => (new RestaurantResource($restaurant->fresh(['address', 'logo'])))->resolve(),
        ]);
    }

    public function updateStaff(Request $request, Restaurant $restaurant, User $user, AdminActionLogger $logger): JsonResponse
    {
        $data = $request->validate([
            'role' => ['required', Rule::enum(RestaurantStaffRole::class)],
        ]);

        $before = DB::table('restaurant_user')
            ->where('restaurant_id', $restaurant->id)
            ->where('user_id', $user->id)
            ->first();

        $restaurant->users()->syncWithoutDetaching([
            $user->id => ['role' => $data['role']],
        ]);

        $logger->log(
            $request->user(),
            'admin.restaurant.staff.update',
            Restaurant::class,
            $restaurant->id,
            before: $before ? (array) $before : null,
            after: ['restaurant_id' => $restaurant->id, 'user_id' => $user->id, 'role' => $data['role']],
        );

        return $this->show($restaurant);
    }

    public function removeStaff(Request $request, Restaurant $restaurant, User $user, AdminActionLogger $logger): JsonResponse
    {
        $before = DB::table('restaurant_user')
            ->where('restaurant_id', $restaurant->id)
            ->where('user_id', $user->id)
            ->first();

        $restaurant->users()->detach($user->id);

        $logger->log(
            $request->user(),
            'admin.restaurant.staff.remove',
            Restaurant::class,
            $restaurant->id,
            before: $before ? (array) $before : null,
        );

        return $this->show($restaurant);
    }
}
