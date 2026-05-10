<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AuthClientType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\Auth\AuthResponseFactory;
use App\Services\Auth\RefreshTokenService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function __invoke(
        RegisterRequest $request,
        RefreshTokenService $refreshTokens,
        AuthResponseFactory $responses,
    )
    {
        $data = $request->validated();

        $user = User::create([
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'name' => $data['name'] ?? null,
        ]);

        $token = Auth::guard('api')->login($user);

        $clientType = AuthClientType::fromNullable($data['client_type'] ?? null);
        [, $refreshToken] = $refreshTokens->issue(
            $user,
            $clientType,
            $request,
            $data['device_name'] ?? null,
        );

        return $responses->authenticated($user, $token, $clientType, $refreshToken, 201);
    }
}
