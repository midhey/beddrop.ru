<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\Auth\InvalidRefreshTokenException;
use App\Http\Controllers\Controller;
use App\Services\Auth\AuthResponseFactory;
use App\Services\Auth\RefreshTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RefreshController extends Controller
{
    public function __invoke(
        Request $request,
        RefreshTokenService $refreshTokens,
        AuthResponseFactory $responses,
    ): JsonResponse
    {
        $plainTextToken = $refreshTokens->extractFromRequest($request);

        if (! $plainTextToken) {
            return $responses->invalidRefreshResponse();
        }

        try {
            [$user, $session, $newRefreshToken] = $refreshTokens->rotate($plainTextToken, $request);
            $accessToken = Auth::guard('api')->login($user);

            return $responses->refreshed(
                $accessToken,
                $session->client_type,
                $newRefreshToken,
            );
        } catch (InvalidRefreshTokenException $e) {
            return $responses->invalidRefreshResponse();
        }
    }
}
