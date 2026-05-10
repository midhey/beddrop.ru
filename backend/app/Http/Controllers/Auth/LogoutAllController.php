<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\AuthResponseFactory;
use App\Services\Auth\RefreshTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutAllController extends Controller
{
    public function __invoke(
        Request $request,
        RefreshTokenService $refreshTokens,
        AuthResponseFactory $responses,
    ): JsonResponse {
        $user = $request->user('api');

        $refreshTokens->revokeAllForUser($user, 'logout_all');
        Auth::guard('api')->logout();

        return $responses->logoutResponse('Вы вышли на всех устройствах');
    }
}
