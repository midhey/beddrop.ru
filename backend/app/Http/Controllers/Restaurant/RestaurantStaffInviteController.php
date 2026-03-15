<?php

namespace App\Http\Controllers\Restaurant;

use App\Actions\Restaurant\AcceptRestaurantStaffInvite;
use App\Actions\Restaurant\CreateRestaurantStaffInvite;
use App\Http\Controllers\Controller;
use App\Http\Requests\Restaurant\CreateStaffInviteRequest;
use App\Models\Restaurant;
use App\Models\RestaurantStaffInvite;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RestaurantStaffInviteController extends Controller
{
    use AuthorizesRequests;

    public function store(
        CreateStaffInviteRequest $request,
        Restaurant $restaurant,
        CreateRestaurantStaffInvite $createRestaurantStaffInvite
    ) {
        $this->authorize('manageStaff', $restaurant);

        $invite = $createRestaurantStaffInvite(
            $restaurant,
            $request->user(),
            $request->validated(),
        );

        return response()->json([
            'invite' => $this->serializeInvite($invite),
        ], Response::HTTP_CREATED);
    }

    public function show(string $token)
    {
        $invite = RestaurantStaffInvite::query()
            ->with(['restaurant', 'invitedBy'])
            ->where('token', $token)
            ->firstOrFail();

        return response()->json([
            'invite' => $this->serializeInvite($invite),
        ]);
    }

    public function accept(
        Request $request,
        string $token,
        AcceptRestaurantStaffInvite $acceptRestaurantStaffInvite
    ) {
        $invite = RestaurantStaffInvite::query()
            ->with('restaurant')
            ->where('token', $token)
            ->firstOrFail();

        $acceptRestaurantStaffInvite($invite, $request->user());

        return response()->json([
            'message' => 'Приглашение принято',
            'invite' => $this->serializeInvite($invite->fresh(['restaurant', 'invitedBy', 'acceptedBy'])),
        ]);
    }

    private function serializeInvite(RestaurantStaffInvite $invite): array
    {
        return [
            'token' => $invite->token,
            'role' => $invite->role,
            'expires_at' => $invite->expires_at,
            'accepted_at' => $invite->accepted_at,
            'restaurant' => [
                'id' => $invite->restaurant->id,
                'name' => $invite->restaurant->name,
                'slug' => $invite->restaurant->slug,
            ],
            'invited_by' => $invite->invitedBy ? [
                'id' => $invite->invitedBy->id,
                'name' => $invite->invitedBy->name,
                'email' => $invite->invitedBy->email,
            ] : null,
            'accepted_by' => $invite->acceptedBy ? [
                'id' => $invite->acceptedBy->id,
                'name' => $invite->acceptedBy->name,
                'email' => $invite->acceptedBy->email,
            ] : null,
        ];
    }
}
