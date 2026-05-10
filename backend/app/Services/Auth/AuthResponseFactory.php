<?php

namespace App\Services\Auth;

use App\Enums\AuthClientType;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Cookie;

class AuthResponseFactory
{
    public function authenticated(
        User $user,
        string $accessToken,
        AuthClientType $clientType,
        ?string $refreshToken = null,
        int $status = 200,
    ): JsonResponse {
        $payload = array_merge(
            $this->accessPayload($accessToken),
            ['user' => $user],
            $this->refreshPayload($clientType, $refreshToken),
        );

        $response = response()->json($payload, $status);

        return $this->attachRefreshCookie($response, $clientType, $refreshToken);
    }

    public function refreshed(
        string $accessToken,
        AuthClientType $clientType,
        ?string $refreshToken = null,
    ): JsonResponse {
        $payload = array_merge(
            $this->accessPayload($accessToken),
            $this->refreshPayload($clientType, $refreshToken),
        );

        $response = response()->json($payload);

        return $this->attachRefreshCookie($response, $clientType, $refreshToken);
    }

    public function logoutResponse(string $message): JsonResponse
    {
        return response()
            ->json(['message' => $message])
            ->withCookie($this->forgetRefreshCookie());
    }

    public function invalidRefreshResponse(
        string $message = 'Сессия истекла. Выполните вход повторно.',
    ): JsonResponse {
        return response()
            ->json(['message' => $message], 401)
            ->withCookie($this->forgetRefreshCookie());
    }

    public function refreshCookieName(): string
    {
        return (string) config('auth_tokens.refresh_cookie_name');
    }

    private function accessPayload(string $accessToken): array
    {
        return [
            'access_token' => $accessToken,
            'token_type' => 'bearer',
            'expires_in' => (int) config('jwt.ttl', 15) * 60,
        ];
    }

    private function refreshPayload(
        AuthClientType $clientType,
        ?string $refreshToken = null,
    ): array {
        if (! $refreshToken || $clientType->usesCookieRefresh()) {
            return [];
        }

        return [
            'refresh_token' => $refreshToken,
            'refresh_expires_in' => (int) config('auth_tokens.refresh_ttl', 43200) * 60,
        ];
    }

    private function attachRefreshCookie(
        JsonResponse $response,
        AuthClientType $clientType,
        ?string $refreshToken,
    ): JsonResponse {
        if (! $clientType->usesCookieRefresh() || ! $refreshToken) {
            return $response;
        }

        return $response->withCookie($this->makeRefreshCookie($refreshToken));
    }

    private function makeRefreshCookie(string $refreshToken): Cookie
    {
        return cookie()->make(
            name: $this->refreshCookieName(),
            value: $refreshToken,
            minutes: (int) config('auth_tokens.refresh_ttl', 43200),
            path: (string) config('auth_tokens.refresh_cookie_path', '/api/v1/auth'),
            domain: config('auth_tokens.refresh_cookie_domain'),
            secure: (bool) config('auth_tokens.refresh_cookie_secure', false),
            httpOnly: true,
            raw: false,
            sameSite: (string) config('auth_tokens.refresh_cookie_same_site', 'lax'),
        );
    }

    private function forgetRefreshCookie(): Cookie
    {
        return cookie()->forget(
            $this->refreshCookieName(),
            (string) config('auth_tokens.refresh_cookie_path', '/api/v1/auth'),
            config('auth_tokens.refresh_cookie_domain'),
        );
    }
}
