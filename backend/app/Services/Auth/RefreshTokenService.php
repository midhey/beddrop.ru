<?php

namespace App\Services\Auth;

use App\Enums\AuthClientType;
use App\Exceptions\Auth\InvalidRefreshTokenException;
use App\Models\AuthRefreshToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RefreshTokenService
{
    public function issue(
        User $user,
        AuthClientType $clientType,
        Request $request,
        ?string $deviceName = null,
        ?string $familyId = null,
        ?AuthRefreshToken $rotatedFrom = null,
    ): array {
        $plainTextToken = Str::random((int) config('auth_tokens.refresh_token_length', 80));
        $now = now();

        $token = AuthRefreshToken::create([
            'user_id' => $user->id,
            'family_id' => $familyId ?? (string) Str::uuid(),
            'rotated_from_id' => $rotatedFrom?->id,
            'client_type' => $clientType,
            'token_hash' => $this->hashToken($plainTextToken),
            'device_name' => $this->resolveDeviceName($clientType, $deviceName),
            'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
            'ip_address' => $request->ip(),
            'last_used_at' => $now,
            'expires_at' => $now->copy()->addMinutes((int) config('auth_tokens.refresh_ttl', 43200)),
        ]);

        return [$token, $plainTextToken];
    }

    public function extractFromRequest(Request $request): ?string
    {
        $requestToken = trim((string) $request->input('refresh_token', ''));

        if ($requestToken !== '') {
            return $requestToken;
        }

        $cookieToken = trim((string) $request->cookie((string) config('auth_tokens.refresh_cookie_name')));

        return $cookieToken !== '' ? $cookieToken : null;
    }

    public function rotate(string $plainTextToken, Request $request): array
    {
        $initialLevel = DB::transactionLevel();
        DB::beginTransaction();

        try {
            $token = AuthRefreshToken::query()
                ->where('token_hash', $this->hashToken($plainTextToken))
                ->lockForUpdate()
                ->first();

            if (! $token) {
                throw new InvalidRefreshTokenException('invalid', 'Сессия не найдена.');
            }

            if ($token->isRevoked()) {
                $this->revokeFamily($token->family_id, 'reuse_detected');
                DB::commit();

                throw new InvalidRefreshTokenException('reuse_detected', 'Старая сессия была использована повторно.');
            }

            if ($token->isExpired()) {
                $this->revokeToken($token, 'expired');
                DB::commit();

                throw new InvalidRefreshTokenException('expired', 'Сессия истекла.');
            }

            $user = $token->user()->first();

            if (! $user || $user->isBanned()) {
                $this->revokeFamily($token->family_id, 'user_unavailable');
                DB::commit();

                throw new InvalidRefreshTokenException('user_unavailable', 'Пользователь недоступен.');
            }

            [$newToken, $newPlainTextToken] = $this->issue(
                $user,
                $token->client_type,
                $request,
                $token->device_name,
                $token->family_id,
                $token,
            );

            $this->revokeToken($token, 'rotated');

            DB::commit();

            return [$user, $newToken, $newPlainTextToken];
        } catch (InvalidRefreshTokenException $e) {
            if (DB::transactionLevel() > $initialLevel) {
                DB::rollBack();
            }

            throw $e;
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > $initialLevel) {
                DB::rollBack();
            }

            throw $e;
        }
    }

    public function revokePlainTextToken(?string $plainTextToken, string $reason = 'logout'): ?AuthRefreshToken
    {
        if (! $plainTextToken) {
            return null;
        }

        return DB::transaction(function () use ($plainTextToken, $reason) {
            $token = AuthRefreshToken::query()
                ->where('token_hash', $this->hashToken($plainTextToken))
                ->lockForUpdate()
                ->first();

            if (! $token || $token->isRevoked()) {
                return $token;
            }

            $this->revokeToken($token, $reason);

            return $token;
        });
    }

    public function revokeAllForUser(User $user, string $reason = 'logout_all'): void
    {
        AuthRefreshToken::query()
            ->where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
                'revoked_reason' => $reason,
                'updated_at' => now(),
            ]);
    }

    private function revokeFamily(string $familyId, string $reason): void
    {
        AuthRefreshToken::query()
            ->where('family_id', $familyId)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
                'revoked_reason' => $reason,
                'updated_at' => now(),
            ]);
    }

    private function revokeToken(AuthRefreshToken $token, string $reason): void
    {
        if ($token->isRevoked()) {
            return;
        }

        $token->forceFill([
            'revoked_at' => now(),
            'revoked_reason' => $reason,
            'last_used_at' => now(),
        ])->save();
    }

    private function hashToken(string $plainTextToken): string
    {
        return hash('sha256', $plainTextToken);
    }

    private function resolveDeviceName(
        AuthClientType $clientType,
        ?string $deviceName,
    ): string {
        $resolved = trim((string) $deviceName);

        return $resolved !== ''
            ? Str::limit($resolved, 255, '')
            : $clientType->defaultDeviceName();
    }
}
