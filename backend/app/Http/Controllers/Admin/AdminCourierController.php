<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CourierProfileStatus;
use App\Enums\CourierShiftStatus;
use App\Enums\CourierVehicle;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\CourierLocation;
use App\Models\CourierProfile;
use App\Models\CourierShift;
use App\Models\User;
use App\Services\Admin\AdminActionLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminCourierController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->integer('per_page', 20), 100);

        $query = CourierProfile::query()
            ->with(['user:id,name,email,phone,is_banned', 'shifts' => fn ($query) => $query->latest('started_at')->limit(1)])
            ->withCount(['orders'])
            ->orderByDesc('updated_at');

        if ($search = trim((string) $request->get('search'))) {
            $query->whereHas('user', function ($query) use ($search) {
                $query
                    ->where('id', is_numeric($search) ? (int) $search : 0)
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($vehicle = $request->get('vehicle')) {
            $query->where('vehicle', $vehicle);
        }

        $profiles = $query->paginate($perPage);
        $userIds = $profiles->getCollection()->pluck('user_id')->all();
        $locations = CourierLocation::query()
            ->whereIn('courier_user_id', $userIds)
            ->orderByDesc('recorded_at')
            ->orderByDesc('created_at')
            ->get()
            ->unique('courier_user_id')
            ->keyBy('courier_user_id');

        $profiles->getCollection()->transform(function (CourierProfile $profile) use ($locations) {
            $profile->setAttribute('latest_location', $locations->get($profile->user_id));
            $profile->setAttribute('open_shift', CourierShift::query()
                ->where('courier_user_id', $profile->user_id)
                ->where('status', CourierShiftStatus::OPEN->value)
                ->latest('started_at')
                ->first());

            return $profile;
        });

        return response()->json($profiles);
    }

    public function show(CourierProfile $courier): JsonResponse
    {
        $courier->load(['user', 'orders.restaurant:id,name,slug', 'orders.deliveryAddress', 'shifts']);
        $latestLocation = CourierLocation::query()
            ->where('courier_user_id', $courier->user_id)
            ->orderByDesc('recorded_at')
            ->orderByDesc('created_at')
            ->first();

        return response()->json([
            'courier' => $courier,
            'latest_location' => $latestLocation,
            'open_shift' => CourierShift::query()
                ->where('courier_user_id', $courier->user_id)
                ->where('status', CourierShiftStatus::OPEN->value)
                ->latest('started_at')
                ->first(),
        ]);
    }

    public function store(Request $request, AdminActionLogger $logger): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'status' => ['nullable', Rule::enum(CourierProfileStatus::class)],
            'vehicle' => ['nullable', Rule::enum(CourierVehicle::class)],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
        ]);

        $user = User::findOrFail($data['user_id']);
        $profile = CourierProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'status' => $data['status'] ?? CourierProfileStatus::ACTIVE->value,
                'vehicle' => $data['vehicle'] ?? null,
                'rating' => $data['rating'] ?? null,
            ],
        );

        $logger->log($request->user(), 'admin.courier.upsert', $profile, after: $profile->toArray());

        return response()->json([
            'courier' => $profile->load('user'),
        ], 201);
    }

    public function update(Request $request, CourierProfile $courier, AdminActionLogger $logger): JsonResponse
    {
        $data = $request->validate([
            'status' => ['sometimes', Rule::enum(CourierProfileStatus::class)],
            'vehicle' => ['sometimes', 'nullable', Rule::enum(CourierVehicle::class)],
            'rating' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:5'],
        ]);

        if (($data['status'] ?? null) === CourierProfileStatus::SUSPENDED->value) {
            $this->ensureCourierCanBeSuspended($courier);
        }

        $before = $courier->only(array_keys($data));
        $courier->fill($data);
        $courier->save();

        $logger->log(
            $request->user(),
            'admin.courier.update',
            $courier,
            before: $before,
            after: $courier->only(array_keys($data)),
        );

        return response()->json([
            'courier' => $courier->fresh(['user']),
        ]);
    }

    private function ensureCourierCanBeSuspended(CourierProfile $courier): void
    {
        $hasOpenShift = $courier->shifts()
            ->where('status', CourierShiftStatus::OPEN->value)
            ->exists();

        if ($hasOpenShift) {
            throw ValidationException::withMessages([
                'status' => 'Нельзя приостановить курьера с открытой сменой.',
            ]);
        }

        $hasActiveOrders = $courier->orders()
            ->whereIn('status', [
                OrderStatus::COURIER_ASSIGNED->value,
                OrderStatus::PICKED_UP->value,
            ])
            ->exists();

        if ($hasActiveOrders) {
            throw ValidationException::withMessages([
                'status' => 'Нельзя приостановить курьера с активным заказом.',
            ]);
        }
    }
}
