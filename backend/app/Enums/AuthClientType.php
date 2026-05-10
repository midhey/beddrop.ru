<?php

namespace App\Enums;

enum AuthClientType: string
{
    case WEB = 'web';
    case MOBILE = 'mobile';

    public static function fromNullable(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::WEB;
    }

    public function defaultDeviceName(): string
    {
        return match ($this) {
            self::WEB => 'Web session',
            self::MOBILE => 'Mobile device',
        };
    }

    public function usesCookieRefresh(): bool
    {
        return $this === self::WEB;
    }
}
