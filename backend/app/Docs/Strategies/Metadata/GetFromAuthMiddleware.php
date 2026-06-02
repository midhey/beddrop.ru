<?php

namespace App\Docs\Strategies\Metadata;

use Illuminate\Support\Str;
use Knuckles\Camel\Extraction\ExtractedEndpointData;
use Knuckles\Scribe\Extracting\Strategies\Strategy;

class GetFromAuthMiddleware extends Strategy
{
    public function __invoke(ExtractedEndpointData $endpointData, array $routeRules = []): array
    {
        $usesAuthMiddleware = collect($endpointData->route->gatherMiddleware())
            ->contains(fn (string $middleware): bool => Str::startsWith($middleware, 'auth'));

        return $usesAuthMiddleware
            ? ['authenticated' => true]
            : [];
    }
}
