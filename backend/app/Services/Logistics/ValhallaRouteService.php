<?php

namespace App\Services\Logistics;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class ValhallaRouteService
{
    public function __construct(
        private readonly LogisticsSettingsService $settings,
    ) {
    }

    /**
     * @param array{lat: float|int|string, lng?: float|int|string, lon?: float|int|string} $start
     * @param array{lat: float|int|string, lng?: float|int|string, lon?: float|int|string} $end
     * @return array{distance_meters: int, duration_seconds: int, encoded_shape: string|null, raw: array<string, mixed>, request: array<string, mixed>}
     */
    public function route(array $start, array $end, string $mode = 'auto'): array
    {
        $baseUrl = config('services.valhalla.url');

        if (!$baseUrl) {
            throw new RuntimeException('VALHALLA_URL is not configured.');
        }

        $payload = [
            'locations' => [
                ['lat' => (float) $start['lat'], 'lon' => (float) ($start['lng'] ?? $start['lon'])],
                ['lat' => (float) $end['lat'], 'lon' => (float) ($end['lng'] ?? $end['lon'])],
            ],
            'costing' => $mode,
            'costing_options' => $this->costingOptions($mode),
            'directions_options' => [
                'language' => 'ru-RU',
            ],
        ];

        $response = Http::baseUrl(rtrim((string) $baseUrl, '/'))
            ->timeout(8)
            ->acceptJson()
            ->post('/route', $payload)
            ->throw();

        $data = $response->json();

        if (!isset($data['trip'])) {
            throw new RuntimeException('Valhalla route response does not contain trip.');
        }

        $summary = $data['trip']['summary'] ?? [];

        return [
            'distance_meters' => (int) round(((float) ($summary['length'] ?? 0)) * 1000),
            'duration_seconds' => (int) round((float) ($summary['time'] ?? 0)),
            'encoded_shape' => $data['trip']['legs'][0]['shape'] ?? null,
            'raw' => $data,
            'request' => $payload,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function costingOptions(string $mode): array
    {
        $settings = $this->settings->all();

        return match ($mode) {
            'bicycle' => [
                'bicycle' => [
                    'use_roads' => $settings['valhalla.bicycle.use_roads'] ?? 0.4,
                    'use_hills' => $settings['valhalla.bicycle.use_hills'] ?? 0.5,
                ],
            ],
            'pedestrian' => [
                'pedestrian' => [
                    'walking_speed' => $settings['valhalla.pedestrian.walking_speed'] ?? 5.1,
                ],
            ],
            default => [
                'auto' => [
                    'shortest' => (bool) ($settings['valhalla.auto.shortest'] ?? false),
                    'use_highways' => $settings['valhalla.auto.use_highways'] ?? 0.5,
                    'use_tolls' => $settings['valhalla.auto.use_tolls'] ?? 0.1,
                    'use_ferry' => $settings['valhalla.auto.use_ferry'] ?? 0.2,
                    'use_unpaved' => $settings['valhalla.auto.use_unpaved'] ?? 0.1,
                ],
            ],
        };
    }
}
