<?php

namespace App\Http\Controllers\Restaurant;

use App\Actions\Restaurant\AddRestaurantStaff;
use App\Actions\Restaurant\RemoveRestaurantStaff;
use App\Actions\Restaurant\UpdateRestaurantStaffRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Restaurant\AddStaffRequest;
use App\Http\Requests\Restaurant\UpdateStaffRoleRequest;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class RestaurantStaffController extends Controller
{
    use AuthorizesRequests;

    public function index(Restaurant $restaurant)
    {
        $this->authorize('manageStaff', $restaurant);

        $restaurant->load(['users' => function ($query) {
            $query->select('users.id', 'users.name', 'users.email', 'users.phone')
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

    public function store(
        AddStaffRequest $request,
        Restaurant $restaurant,
        AddRestaurantStaff $addRestaurantStaff
    )
    {
        $this->authorize('manageStaff', $restaurant);

        $data = $request->validated();
        $addRestaurantStaff($restaurant, $data);

        return response()->json([
            'message' => 'Сотрудник добавлен',
        ], 201);
    }

    public function update(
        UpdateStaffRoleRequest $request,
        Restaurant $restaurant,
        User $user,
        UpdateRestaurantStaffRole $updateRestaurantStaffRole
    )
    {
        $this->authorize('manageStaff', $restaurant);

        $data = $request->validated();
        $updateRestaurantStaffRole($restaurant, $user, $data);

        return response()->json([
            'message' => 'Роль сотрудника обновлена',
        ]);
    }

    public function destroy(
        Request $request,
        Restaurant $restaurant,
        User $user,
        RemoveRestaurantStaff $removeRestaurantStaff
    )
    {
        $this->authorize('manageStaff', $restaurant);
        $removeRestaurantStaff($request->user(), $restaurant, $user);

        return response()->json([
            'message' => 'Сотрудник удалён',
        ]);
    }
}
