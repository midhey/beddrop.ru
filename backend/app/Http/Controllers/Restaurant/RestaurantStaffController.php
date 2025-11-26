<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Restaurant\AddStaffRequest;
use App\Http\Requests\Restaurant\UpdateStaffRoleRequest;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class RestaurantStaffController extends Controller
{
    use AuthorizesRequests;

    public function index(Restaurant $restaurant)
    {
        $this->authorize('view', $restaurant);

        $restaurant->load(['users' => function ($q) {
            $q->select('users.id', 'users.name', 'users.email', 'users.phone')
                ->orderBy('users.name');
        }]);

        return response()->json([
            'staff' => $restaurant->users->map(function (User $user) {
                return [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role'  => $user->pivot->role,
                ];
            }),
        ]);
    }

    public function store(AddStaffRequest $request, Restaurant $restaurant)
    {
        $this->authorize('manageStaff', $restaurant);

        $data = $request->validated();

        $user = User::findOrFail($data['user_id']);

        // нельзя добавить владельца, который уже есть
        if ($restaurant->users()->where('users.id', $user->id)->exists()) {
            return response()->json([
                'message' => 'Пользователь уже привязан к этому ресторану',
            ], 422);
        }

        $restaurant->users()->attach($user->id, [
            'role' => $data['role'],
        ]);

        return response()->json([
            'message' => 'Сотрудник добавлен',
        ], 201);
    }

    public function update(UpdateStaffRoleRequest $request, Restaurant $restaurant, User $user)
    {
        $this->authorize('manageStaff', $restaurant);

        if (! $restaurant->users()->where('users.id', $user->id)->exists()) {
            return response()->json([
                'message' => 'Пользователь не является сотрудником этого ресторана',
            ], 404);
        }

        $data = $request->validated();

        $restaurant->users()->updateExistingPivot($user->id, [
            'role' => $data['role'],
        ]);

        return response()->json([
            'message' => 'Роль сотрудника обновлена',
        ]);
    }

    public function destroy(Restaurant $restaurant, User $user)
    {
        $this->authorize('manageStaff', $restaurant);

        if (! $restaurant->users()->where('users.id', $user->id)->exists()) {
            return response()->json([
                'message' => 'Пользователь не является сотрудником этого ресторана',
            ], 404);
        }

        // не даём удалить себя как OWNER (чтобы ресторан не остался без владельца)
        if (auth('api')->id() === $user->id) {
            return response()->json([
                'message' => 'Нельзя удалить самого себя из ресторана',
            ], 422);
        }

        $restaurant->users()->detach($user->id);

        return response()->json([
            'message' => 'Сотрудник удалён',
        ]);
    }
}
