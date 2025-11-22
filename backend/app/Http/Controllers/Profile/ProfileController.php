<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Profile\UpdateProfileRequest;
use Illuminate\Http\Request;

class ProfileController extends Controller
{

    public function show(Request $request) {
        return response()->json([
            'user'=>$request->user(),
        ]);

    }
    public function update(UpdateProfileRequest $request) {
        $user = $request->user();
        $data = $request->validated();

        $user->name = $data['name'] ?? $user->name;
        $user->email = $data['email'] ?? $user->email;
        $user->phone = $data['phone'] ?? $user->phone;

        $user->save();

        return response()->json([
            'user'=>$user,
        ]);
    }
}
