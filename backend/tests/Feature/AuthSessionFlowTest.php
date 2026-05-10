<?php

namespace Tests\Feature;

use App\Models\AuthRefreshToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Cookie;
use Tests\Concerns\CreatesApiData;
use Tests\TestCase;

class AuthSessionFlowTest extends TestCase
{
    use CreatesApiData;
    use RefreshDatabase;

    public function test_web_register_sets_refresh_cookie_and_creates_user(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'email' => 'web-register@example.com',
            'phone' => '79990001122',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'name' => 'Web Register',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('user.email', 'web-register@example.com')
            ->assertJsonStructure(['access_token', 'token_type', 'expires_in', 'user']);

        $cookie = $this->refreshCookieFromResponse($response);

        $this->assertNotSame('', (string) $cookie->getValue());
        $this->assertDatabaseHas('users', [
            'email' => 'web-register@example.com',
            'phone' => '79990001122',
        ]);
        $this->assertDatabaseHas('auth_refresh_tokens', [
            'client_type' => 'web',
            'device_name' => 'Web session',
        ]);
    }

    public function test_mobile_register_returns_refresh_token_in_body(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'email' => 'mobile-register@example.com',
            'phone' => '79990001123',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'name' => 'Mobile Register',
            'client_type' => 'mobile',
            'device_name' => 'iPhone 16 Pro',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('user.email', 'mobile-register@example.com')
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
                'refresh_token',
                'refresh_expires_in',
                'user',
            ]);

        $this->assertFalse($this->responseHasRefreshCookie($response));
        $this->assertDatabaseHas('auth_refresh_tokens', [
            'client_type' => 'mobile',
            'device_name' => 'iPhone 16 Pro',
        ]);
    }

    public function test_web_login_sets_refresh_cookie_and_returns_access_token(): void
    {
        $user = $this->createUser();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonStructure(['access_token', 'token_type', 'expires_in', 'user']);

        $this->assertNull($response->json('refresh_token'));

        $cookie = $this->refreshCookieFromResponse($response);

        $this->assertSame($this->refreshCookieName(), $cookie->getName());
        $this->assertNotSame('', (string) $cookie->getValue());
        $this->assertSame('/api/v1/auth', $cookie->getPath());
        $this->assertTrue($cookie->isHttpOnly());

        $this->assertDatabaseHas('auth_refresh_tokens', [
            'user_id' => $user->id,
            'client_type' => 'web',
            'device_name' => 'Web session',
        ]);
    }

    public function test_mobile_login_returns_refresh_token_in_body_without_cookie(): void
    {
        $user = $this->createUser();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'client_type' => 'mobile',
            'device_name' => 'iPhone 16',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
                'refresh_token',
                'refresh_expires_in',
                'user',
            ]);

        $this->assertFalse($this->responseHasRefreshCookie($response));
        $this->assertDatabaseHas('auth_refresh_tokens', [
            'user_id' => $user->id,
            'client_type' => 'mobile',
            'device_name' => 'iPhone 16',
        ]);
    }

    public function test_web_refresh_rotates_cookie_token_and_returns_new_access_token(): void
    {
        $user = $this->createUser();

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $oldCookie = $this->refreshCookieFromResponse($loginResponse);
        $oldHash = hash('sha256', (string) $oldCookie->getValue());

        $refreshResponse = $this
            ->postJsonWithRefreshCookie('/api/v1/auth/refresh', (string) $oldCookie->getValue());

        $refreshResponse
            ->assertOk()
            ->assertJsonStructure(['access_token', 'token_type', 'expires_in']);

        $newCookie = $this->refreshCookieFromResponse($refreshResponse);
        $newHash = hash('sha256', (string) $newCookie->getValue());

        $this->assertNotSame((string) $oldCookie->getValue(), (string) $newCookie->getValue());

        $this->assertDatabaseHas('auth_refresh_tokens', [
            'token_hash' => $oldHash,
            'revoked_reason' => 'rotated',
        ]);
        $this->assertDatabaseHas('auth_refresh_tokens', [
            'token_hash' => $newHash,
            'user_id' => $user->id,
            'client_type' => 'web',
        ]);
    }

    public function test_expired_refresh_token_cannot_be_used(): void
    {
        $user = $this->createUser();

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'client_type' => 'mobile',
        ]);

        $refreshToken = (string) $loginResponse->json('refresh_token');
        $refreshTokenHash = hash('sha256', $refreshToken);

        $this->travel(config('auth_tokens.refresh_ttl') + 1)->minutes();

        $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $refreshToken,
        ])->assertUnauthorized();

        $this->assertDatabaseHas('auth_refresh_tokens', [
            'token_hash' => $refreshTokenHash,
            'revoked_reason' => 'expired',
        ]);
    }

    public function test_mobile_refresh_reuse_revokes_entire_family(): void
    {
        $user = $this->createUser();

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'client_type' => 'mobile',
            'device_name' => 'Pixel 10',
        ]);

        $firstRefreshToken = (string) $loginResponse->json('refresh_token');
        $firstHash = hash('sha256', $firstRefreshToken);

        $rotateResponse = $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $firstRefreshToken,
        ]);

        $rotateResponse->assertOk();

        $secondRefreshToken = (string) $rotateResponse->json('refresh_token');
        $secondHash = hash('sha256', $secondRefreshToken);

        $replayResponse = $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $firstRefreshToken,
        ]);

        $replayResponse->assertUnauthorized();

        $secondUseResponse = $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $secondRefreshToken,
        ]);

        $secondUseResponse->assertUnauthorized();

        $secondToken = AuthRefreshToken::query()->where('token_hash', $secondHash)->firstOrFail();

        $this->assertDatabaseHas('auth_refresh_tokens', [
            'token_hash' => $firstHash,
            'revoked_reason' => 'rotated',
        ]);
        $this->assertDatabaseHas('auth_refresh_tokens', [
            'token_hash' => $secondHash,
            'revoked_reason' => 'reuse_detected',
        ]);
        $this->assertSame(0, AuthRefreshToken::query()
            ->where('family_id', $secondToken->family_id)
            ->whereNull('revoked_at')
            ->count());
    }

    public function test_banned_user_cannot_refresh_and_session_family_is_revoked(): void
    {
        $user = $this->createUser();

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'client_type' => 'mobile',
        ]);

        $refreshToken = (string) $loginResponse->json('refresh_token');
        $refreshTokenHash = hash('sha256', $refreshToken);

        $user->forceFill(['is_banned' => true])->save();

        $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $refreshToken,
        ])->assertUnauthorized();

        $token = AuthRefreshToken::query()->where('token_hash', $refreshTokenHash)->firstOrFail();

        $this->assertSame(0, AuthRefreshToken::query()
            ->where('family_id', $token->family_id)
            ->whereNull('revoked_at')
            ->count());
        $this->assertDatabaseHas('auth_refresh_tokens', [
            'token_hash' => $refreshTokenHash,
            'revoked_reason' => 'user_unavailable',
        ]);
    }

    public function test_logout_revokes_current_refresh_token_and_clears_cookie(): void
    {
        $user = $this->createUser();

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $cookie = $this->refreshCookieFromResponse($loginResponse);
        $accessToken = (string) $loginResponse->json('access_token');
        $tokenHash = hash('sha256', (string) $cookie->getValue());

        $logoutResponse = $this
            ->postJsonWithRefreshCookie(
                '/api/v1/auth/logout',
                (string) $cookie->getValue(),
                [],
                ['Authorization' => "Bearer {$accessToken}"],
            );

        $logoutResponse
            ->assertOk()
            ->assertJsonPath('message', 'Вы успешно вышли из своего аккаунта');

        $forgottenCookie = $this->refreshCookieFromResponse($logoutResponse);
        $this->assertTrue($forgottenCookie->isCleared());

        $this->assertDatabaseHas('auth_refresh_tokens', [
            'token_hash' => $tokenHash,
            'revoked_reason' => 'logout',
        ]);

        $this
            ->postJsonWithRefreshCookie('/api/v1/auth/refresh', (string) $cookie->getValue())
            ->assertUnauthorized();
    }

    public function test_logout_all_revokes_every_user_refresh_session(): void
    {
        $user = $this->createUser();

        $webLogin = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $mobileLogin = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'client_type' => 'mobile',
            'device_name' => 'Android',
        ]);

        $accessToken = (string) $mobileLogin->json('access_token');

        $response = $this
            ->withHeader('Authorization', "Bearer {$accessToken}")
            ->postJson('/api/v1/auth/logout-all');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Вы вышли на всех устройствах');

        $this->assertSame(
            0,
            AuthRefreshToken::query()
                ->where('user_id', $user->id)
                ->whereNull('revoked_at')
                ->count(),
        );

        $webCookie = $this->refreshCookieFromResponse($webLogin);
        $mobileRefreshToken = (string) $mobileLogin->json('refresh_token');

        $this
            ->postJsonWithRefreshCookie('/api/v1/auth/refresh', (string) $webCookie->getValue())
            ->assertUnauthorized();

        $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $mobileRefreshToken,
        ])->assertUnauthorized();
    }

    public function test_refresh_cookie_uses_configured_security_attributes(): void
    {
        config()->set('auth_tokens.refresh_cookie_secure', true);
        config()->set('auth_tokens.refresh_cookie_same_site', 'strict');
        config()->set('auth_tokens.refresh_cookie_domain', '.beddrop.test');
        config()->set('auth_tokens.refresh_cookie_path', '/custom-auth');

        $user = $this->createUser();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $cookie = $this->refreshCookieFromResponse($response);

        $this->assertTrue($cookie->isSecure());
        $this->assertSame('strict', $cookie->getSameSite());
        $this->assertSame('.beddrop.test', $cookie->getDomain());
        $this->assertSame('/custom-auth', $cookie->getPath());
    }

    public function test_access_tokens_from_login_and_refresh_can_access_profile_route(): void
    {
        $user = $this->createUser();

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $loginAccessToken = (string) $loginResponse->json('access_token');
        $refreshCookie = $this->refreshCookieFromResponse($loginResponse);

        $this->withHeader('Authorization', "Bearer {$loginAccessToken}")
            ->getJson('/api/v1/profile/me')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);

        $refreshResponse = $this->postJsonWithRefreshCookie(
            '/api/v1/auth/refresh',
            (string) $refreshCookie->getValue(),
        );

        $refreshResponse->assertOk();

        $refreshedAccessToken = (string) $refreshResponse->json('access_token');

        $this->withHeader('Authorization', "Bearer {$refreshedAccessToken}")
            ->getJson('/api/v1/profile/me')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);
    }

    private function refreshCookieName(): string
    {
        return (string) config('auth_tokens.refresh_cookie_name');
    }

    private function refreshCookieFromResponse($response): Cookie
    {
        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() === $this->refreshCookieName()) {
                return $cookie;
            }
        }

        $this->fail('Refresh cookie was not found in response.');
    }

    private function responseHasRefreshCookie($response): bool
    {
        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() === $this->refreshCookieName()) {
                return true;
            }
        }

        return false;
    }

    private function postJsonWithRefreshCookie(
        string $uri,
        string $refreshToken,
        array $payload = [],
        array $headers = [],
    ) {
        $server = [
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ];

        foreach ($headers as $name => $value) {
            $server['HTTP_'.strtoupper(str_replace('-', '_', $name))] = $value;
        }

        return $this->call(
            'POST',
            $uri,
            $payload,
            [$this->refreshCookieName() => $refreshToken],
            [],
            $server,
        );
    }
}
