<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = [
        'user_id',
        'label',
        'value',
        'unrestricted_value',
        'line1',
        'line2',
        'city',
        'postal_code',
        'country',
        'country_iso_code',
        'federal_district',
        'region_fias_id',
        'region_kladr_id',
        'region_iso_code',
        'region_with_type',
        'region_type',
        'region_type_full',
        'region',
        'area_fias_id',
        'area_kladr_id',
        'area_with_type',
        'area_type',
        'area_type_full',
        'area',
        'city_fias_id',
        'city_kladr_id',
        'city_with_type',
        'city_type',
        'city_type_full',
        'settlement_fias_id',
        'settlement_kladr_id',
        'settlement_with_type',
        'settlement_type',
        'settlement_type_full',
        'settlement',
        'street_fias_id',
        'street_kladr_id',
        'street_with_type',
        'street_type',
        'street_type_full',
        'street',
        'house_fias_id',
        'house_kladr_id',
        'house_type',
        'house_type_full',
        'house',
        'block_type',
        'block_type_full',
        'block',
        'flat_type',
        'flat_type_full',
        'flat',
        'entrance',
        'floor',
        'intercom',
        'lat',
        'lng',
        'fias_id',
        'kladr_id',
        'qc_geo',
        'timezone',
        'beltway_hit',
        'beltway_distance',
        'metro_json',
        'raw_dadata_json',
        'geo_source',
        'geocoded_at',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'qc_geo' => 'integer',
        'beltway_distance' => 'decimal:2',
        'metro_json' => 'array',
        'raw_dadata_json' => 'array',
        'geocoded_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function restaurants()
    {
        return $this->hasMany(Restaurant::class, 'address_id');
    }

    public function displayValue(): string
    {
        return $this->value
            ?: $this->unrestricted_value
            ?: trim(implode(', ', array_filter([$this->city, $this->line1])));
    }
}
