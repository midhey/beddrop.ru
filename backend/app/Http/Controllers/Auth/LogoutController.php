<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\AuthResponseFactory;
use App\Services\Auth\RefreshTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class LogoutController extends Controller
{
    public function __invoke(
        Request $request,
        RefreshTokenService $refreshTokens,
        AuthResponseFactory $responses,
    ): JsonResponse
    {
        $refreshTokens->revokePlainTextToken(
            $refreshTokens->extractFromRequest($request),
            'logout',
        );

        $accessToken = $request->bearerToken();

        if ($accessToken) {
            try {
                JWTAuth::setToken($accessToken)->invalidate();
            } catch (\Throwable) {
                // Ignore invalid or already expired access tokens during logout.
            }
        }

        return $responses->logoutResponse('Вы успешно вышли из своего аккаунта');
    }
}
