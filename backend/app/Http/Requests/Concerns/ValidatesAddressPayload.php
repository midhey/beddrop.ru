<?php

namespace App\Http\Requests\Concerns;

trait ValidatesAddressPayload
{
    /**
     * @return array<string, array<int, string>>
     */
    protected function addressRules(string $prefix = '', bool $partial = false): array
    {
        $p = $prefix;
        $required = $partial ? 'sometimes' : 'required_without:' . $p . 'line1';
        $sometimes = $partial ? 'sometimes' : 'nullable';

        return [
            $p . 'label' => ['nullable', 'string', 'max:255'],
            $p . 'value' => [$required, 'string', 'max:255'],
            $p . 'unrestricted_value' => [$sometimes, 'string', 'max:255'],
            $p . 'line1' => [$partial ? 'sometimes' : 'nullable', 'string', 'max:255'],
            $p . 'line2' => ['nullable', 'string', 'max:255'],
            $p . 'city' => ['nullable', 'string', 'max:255'],
            $p . 'postal_code' => ['nullable', 'string', 'max:32'],
            $p . 'country' => ['nullable', 'string', 'max:255'],
            $p . 'country_iso_code' => ['nullable', 'string', 'max:2'],
            $p . 'federal_district' => ['nullable', 'string', 'max:255'],
            $p . 'region_fias_id' => ['nullable', 'string', 'max:255'],
            $p . 'region_kladr_id' => ['nullable', 'string', 'max:255'],
            $p . 'region_iso_code' => ['nullable', 'string', 'max:255'],
            $p . 'region_with_type' => ['nullable', 'string', 'max:255'],
            $p . 'region_type' => ['nullable', 'string', 'max:255'],
            $p . 'region_type_full' => ['nullable', 'string', 'max:255'],
            $p . 'region' => ['nullable', 'string', 'max:255'],
            $p . 'area_fias_id' => ['nullable', 'string', 'max:255'],
            $p . 'area_kladr_id' => ['nullable', 'string', 'max:255'],
            $p . 'area_with_type' => ['nullable', 'string', 'max:255'],
            $p . 'area_type' => ['nullable', 'string', 'max:255'],
            $p . 'area_type_full' => ['nullable', 'string', 'max:255'],
            $p . 'area' => ['nullable', 'string', 'max:255'],
            $p . 'city_fias_id' => ['nullable', 'string', 'max:255'],
            $p . 'city_kladr_id' => ['nullable', 'string', 'max:255'],
            $p . 'city_with_type' => ['nullable', 'string', 'max:255'],
            $p . 'city_type' => ['nullable', 'string', 'max:255'],
            $p . 'city_type_full' => ['nullable', 'string', 'max:255'],
            $p . 'settlement_fias_id' => ['nullable', 'string', 'max:255'],
            $p . 'settlement_kladr_id' => ['nullable', 'string', 'max:255'],
            $p . 'settlement_with_type' => ['nullable', 'string', 'max:255'],
            $p . 'settlement_type' => ['nullable', 'string', 'max:255'],
            $p . 'settlement_type_full' => ['nullable', 'string', 'max:255'],
            $p . 'settlement' => ['nullable', 'string', 'max:255'],
            $p . 'street_fias_id' => ['nullable', 'string', 'max:255'],
            $p . 'street_kladr_id' => ['nullable', 'string', 'max:255'],
            $p . 'street_with_type' => ['nullable', 'string', 'max:255'],
            $p . 'street_type' => ['nullable', 'string', 'max:255'],
            $p . 'street_type_full' => ['nullable', 'string', 'max:255'],
            $p . 'street' => ['nullable', 'string', 'max:255'],
            $p . 'house_fias_id' => ['nullable', 'string', 'max:255'],
            $p . 'house_kladr_id' => ['nullable', 'string', 'max:255'],
            $p . 'house_type' => ['nullable', 'string', 'max:255'],
            $p . 'house_type_full' => ['nullable', 'string', 'max:255'],
            $p . 'house' => ['nullable', 'string', 'max:255'],
            $p . 'block_type' => ['nullable', 'string', 'max:255'],
            $p . 'block_type_full' => ['nullable', 'string', 'max:255'],
            $p . 'block' => ['nullable', 'string', 'max:255'],
            $p . 'flat_type' => ['nullable', 'string', 'max:255'],
            $p . 'flat_type_full' => ['nullable', 'string', 'max:255'],
            $p . 'flat' => ['nullable', 'string', 'max:255'],
            $p . 'entrance' => ['nullable', 'string', 'max:255'],
            $p . 'floor' => ['nullable', 'string', 'max:255'],
            $p . 'intercom' => ['nullable', 'string', 'max:255'],
            $p . 'lat' => [$partial ? 'sometimes' : 'required', 'numeric'],
            $p . 'lng' => [$partial ? 'sometimes' : 'required', 'numeric'],
            $p . 'fias_id' => ['nullable', 'string', 'max:255'],
            $p . 'kladr_id' => ['nullable', 'string', 'max:255'],
            $p . 'qc_geo' => ['nullable', 'integer', 'min:0'],
            $p . 'timezone' => ['nullable', 'string', 'max:255'],
            $p . 'beltway_hit' => ['nullable', 'string', 'max:255'],
            $p . 'beltway_distance' => ['nullable', 'numeric'],
            $p . 'metro_json' => ['nullable', 'array'],
            $p . 'raw_dadata_json' => ['nullable', 'array'],
            $p . 'geo_source' => ['nullable', 'string', 'max:255'],
        ];
    }
}
