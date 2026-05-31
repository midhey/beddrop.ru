<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Admin\AdminActionLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->integer('per_page', 20), 100);

        $query = User::query()
            ->withCount(['orders', 'restaurants'])
            ->with(['courierProfile'])
            ->orderByDesc('created_at');

        if ($search = trim((string) $request->get('search'))) {
            $query->where(function ($query) use ($search) {
                $query
                    ->where('id', is_numeric($search) ? (int) $search : 0)
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        foreach (['is_admin', 'is_banned'] as $flag) {
            if ($request->has($flag) && $request->get($flag) !== '') {
                $query->where($flag, $request->boolean($flag));
            }
        }

        if ($request->boolean('has_courier_profile')) {
            $query->whereHas('courierProfile');
        }

        if ($request->boolean('has_restaurants')) {
            $query->whereHas('restaurants');
        }

        return response()->json($query->paginate($perPage));
    }

    public function show(User $user): JsonResponse
    {
        $user->load([
            'courierProfile',
            'restaurants.address',
            'orders.restaurant:id,name,slug',
        ])->loadCount(['orders', 'restaurants']);

        return response()->json([
            'user' => $user,
        ]);
    }

    public function update(Request $request, User $user, AdminActionLogger $logger): JsonResponse
    {
        $data = $request->validate([
            'is_admin' => ['sometimes', 'boolean'],
            'is_banned' => ['sometimes', 'boolean'],
        ]);

        if ($data === []) {
            return response()->json(['user' => $user]);
        }

        $before = $user->only(array_keys($data));
        $user->forceFill($data);
        $user->save();

        $logger->log(
            $request->user(),
            'admin.user.update',
            $user,
            before: $before,
            after: $user->only(array_keys($data)),
        );

        return response()->json([
            'user' => $user->fresh(['courierProfile'])->loadCount(['orders', 'restaurants']),
        ]);
    }
}
