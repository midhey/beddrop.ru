<?php

use Illuminate\Support\Str;

$defaultCookieName = Str::of((string) env('APP_NAME', 'beddrop'))
    ->slug('_')
    ->append('_refresh_token')
    ->value();

return [
    'refresh_ttl' => (int) env('AUTH_REFRESH_TOKEN_TTL', 43200),
    'refresh_token_length' => (int) env('AUTH_REFRESH_TOKEN_LENGTH', 80),
    'refresh_cookie_name' => env('AUTH_REFRESH_COOKIE_NAME', $defaultCookieName),
    'refresh_cookie_domain' => env('AUTH_REFRESH_COOKIE_DOMAIN', env('SESSION_DOMAIN')),
    'refresh_cookie_path' => env('AUTH_REFRESH_COOKIE_PATH', '/api/v1/auth'),
    'refresh_cookie_secure' => env('AUTH_REFRESH_COOKIE_SECURE', env('APP_ENV', 'production') === 'production'),
    'refresh_cookie_same_site' => env('AUTH_REFRESH_COOKIE_SAME_SITE', 'lax'),
];
