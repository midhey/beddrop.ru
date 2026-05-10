<?php

namespace App\Services\Geo;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class DadataAddressService
{
    public function __construct(
        private readonly AddressDataMapper $mapper,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function suggestions(string $query, int $count = 10): array
    {
        $this->ensureApiKey();

        $response = Http::baseUrl('https://suggestions.dadata.ru')
            ->timeout(5)
            ->acceptJson()
            ->withHeaders([
                'Authorization' => 'Token ' . config('services.dadata.api_key'),
            ])
            ->post('/suggestions/api/4_1/rs/suggest/address', [
                'query' => $query,
                'count' => $count,
            ])
            ->throw();

        return collect($response->json('suggestions', []))
            ->map(fn (array $suggestion) => [
                'value' => $suggestion['value'] ?? null,
                'unrestricted_value' => $suggestion['unrestricted_value'] ?? null,
                'data' => $this->mapper->fromDadataData($suggestion['data'] ?? [], $suggestion['value'] ?? null),
                'raw' => $suggestion,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     *
     * @throws RequestException
     */
    public function clean(string $address): array
    {
        $this->ensureCleanCredentials();

        $response = Http::baseUrl('https://cleaner.dadata.ru')
            ->timeout(5)
            ->acceptJson()
            ->withHeaders([
                'Authorization' => 'Token ' . config('services.dadata.api_key'),
                'X-Secret' => (string) config('services.dadata.secret_key'),
            ])
            ->post('/api/v1/clean/address', [$address])
            ->throw();

        $result = $response->json('0', []);

        return [
            'value' => $result['result'] ?? $address,
            'data' => $this->mapper->fromDadataData($result, $result['result'] ?? $address),
            'raw' => $result,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function reverseGeocode(float $lat, float $lng): array
    {
        $this->ensureApiKey();

        $response = Http::baseUrl('https://suggestions.dadata.ru')
            ->timeout(5)
            ->acceptJson()
            ->withHeaders([
                'Authorization' => 'Token ' . config('services.dadata.api_key'),
            ])
            ->post('/suggestions/api/4_1/rs/geolocate/address', [
                'lat' => $lat,
                'lon' => $lng,
                'radius_meters' => 50,
            ])
            ->throw();

        $suggestion = $response->json('suggestions.0', []);
        $data = $suggestion['data'] ?? [];
        $mapped = $this->mapper->fromDadataData($data, $suggestion['value'] ?? null, 'dadata_reverse');

        $mapped['lat'] ??= $lat;
        $mapped['lng'] ??= $lng;

        return [
            'value' => $suggestion['value'] ?? null,
            'data' => $mapped,
            'raw' => $suggestion,
        ];
    }

    private function ensureApiKey(): void
    {
        if (!config('services.dadata.api_key')) {
            throw new RuntimeException('DADATA_API_KEY is not configured.');
        }
    }

    private function ensureCleanCredentials(): void
    {
        $this->ensureApiKey();

        if (!config('services.dadata.secret_key')) {
            throw new RuntimeException('DADATA_SECRET_KEY is not configured.');
        }
    }
}
