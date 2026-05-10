<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AuthClientType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Auth\AuthResponseFactory;
use App\Services\Auth\RefreshTokenService;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function __invoke(
        LoginRequest $request,
        RefreshTokenService $refreshTokens,
        AuthResponseFactory $responses,
    ) {
        $validated = $request->validated();
        $credentials = [
            'email' => $validated['email'],
            'password' => $validated['password'],
        ];

        if (! $token = Auth::guard('api')->attempt($credentials)) {
            return response()->json(['message' => 'Неверные данные'], 401);
        }

        $user = Auth::guard('api')->user();
        if ($user->isBanned()) {
            Auth::guard('api')->logout();

            return response()->json(['message' => 'Ваш аккаунт был заблокирован'], 403);
        }

        $clientType = AuthClientType::fromNullable($validated['client_type'] ?? null);
        [, $refreshToken] = $refreshTokens->issue(
            $user,
            $clientType,
            $request,
            $validated['device_name'] ?? null,
        );

        return $responses->authenticated($user, $token, $clientType, $refreshToken);
    }
}
