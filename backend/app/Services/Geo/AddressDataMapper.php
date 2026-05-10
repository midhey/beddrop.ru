<?php

namespace App\Services\Geo;

use Illuminate\Support\Carbon;

class AddressDataMapper
{
    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function fromDadataData(array $data, ?string $value = null, ?string $source = 'dadata'): array
    {
        $lat = $this->floatOrNull($data['geo_lat'] ?? null);
        $lng = $this->floatOrNull($data['geo_lon'] ?? null);

        return [
            'value' => $value ?: ($data['result'] ?? $data['unrestricted_value'] ?? null),
            'unrestricted_value' => $data['unrestricted_value'] ?? $data['result'] ?? $value,
            'line1' => $this->buildLine1($data),
            'line2' => $this->buildLine2($data),
            'city' => $data['city'] ?? $data['settlement'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'country' => $data['country'] ?? null,
            'country_iso_code' => $data['country_iso_code'] ?? null,
            'federal_district' => $data['federal_district'] ?? null,
            'region_fias_id' => $data['region_fias_id'] ?? null,
            'region_kladr_id' => $data['region_kladr_id'] ?? null,
            'region_iso_code' => $data['region_iso_code'] ?? null,
            'region_with_type' => $data['region_with_type'] ?? null,
            'region_type' => $data['region_type'] ?? null,
            'region_type_full' => $data['region_type_full'] ?? null,
            'region' => $data['region'] ?? null,
            'area_fias_id' => $data['area_fias_id'] ?? null,
            'area_kladr_id' => $data['area_kladr_id'] ?? null,
            'area_with_type' => $data['area_with_type'] ?? null,
            'area_type' => $data['area_type'] ?? null,
            'area_type_full' => $data['area_type_full'] ?? null,
            'area' => $data['area'] ?? null,
            'city_fias_id' => $data['city_fias_id'] ?? null,
            'city_kladr_id' => $data['city_kladr_id'] ?? null,
            'city_with_type' => $data['city_with_type'] ?? null,
            'city_type' => $data['city_type'] ?? null,
            'city_type_full' => $data['city_type_full'] ?? null,
            'settlement_fias_id' => $data['settlement_fias_id'] ?? null,
            'settlement_kladr_id' => $data['settlement_kladr_id'] ?? null,
            'settlement_with_type' => $data['settlement_with_type'] ?? null,
            'settlement_type' => $data['settlement_type'] ?? null,
            'settlement_type_full' => $data['settlement_type_full'] ?? null,
            'settlement' => $data['settlement'] ?? null,
            'street_fias_id' => $data['street_fias_id'] ?? null,
            'street_kladr_id' => $data['street_kladr_id'] ?? null,
            'street_with_type' => $data['street_with_type'] ?? null,
            'street_type' => $data['street_type'] ?? null,
            'street_type_full' => $data['street_type_full'] ?? null,
            'street' => $data['street'] ?? null,
            'house_fias_id' => $data['house_fias_id'] ?? null,
            'house_kladr_id' => $data['house_kladr_id'] ?? null,
            'house_type' => $data['house_type'] ?? null,
            'house_type_full' => $data['house_type_full'] ?? null,
            'house' => $data['house'] ?? null,
            'block_type' => $data['block_type'] ?? null,
            'block_type_full' => $data['block_type_full'] ?? null,
            'block' => $data['block'] ?? null,
            'flat_type' => $data['flat_type'] ?? null,
            'flat_type_full' => $data['flat_type_full'] ?? null,
            'flat' => $data['flat'] ?? null,
            'entrance' => $data['entrance'] ?? null,
            'floor' => $data['floor'] ?? null,
            'lat' => $lat,
            'lng' => $lng,
            'fias_id' => $data['fias_id'] ?? null,
            'kladr_id' => $data['kladr_id'] ?? null,
            'qc_geo' => isset($data['qc_geo']) ? (int) $data['qc_geo'] : null,
            'timezone' => $data['timezone'] ?? null,
            'beltway_hit' => $data['beltway_hit'] ?? null,
            'beltway_distance' => $this->floatOrNull($data['beltway_distance'] ?? null),
            'metro_json' => $data['metro'] ?? null,
            'raw_dadata_json' => $data,
            'geo_source' => $source,
            'geocoded_at' => ($lat !== null && $lng !== null) ? Carbon::now() : null,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function buildLine1(array $data): ?string
    {
        $parts = [];

        if (!empty($data['street_with_type'])) {
            $parts[] = $data['street_with_type'];
        }

        if (!empty($data['house'])) {
            $house = trim((string) (($data['house_type_full'] ?? 'дом') . ' ' . $data['house']));
            $parts[] = $house;
        }

        if (!empty($data['block'])) {
            $block = trim((string) (($data['block_type_full'] ?? 'корпус') . ' ' . $data['block']));
            $parts[] = $block;
        }

        return $parts ? implode(', ', $parts) : ($data['result'] ?? null);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function buildLine2(array $data): ?string
    {
        $parts = [];

        if (!empty($data['entrance'])) {
            $parts[] = 'подъезд ' . $data['entrance'];
        }

        if (!empty($data['floor'])) {
            $parts[] = 'этаж ' . $data['floor'];
        }

        if (!empty($data['flat'])) {
            $parts[] = 'кв. ' . $data['flat'];
        }

        return $parts ? implode(', ', $parts) : null;
    }

    private function floatOrNull(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }
}
