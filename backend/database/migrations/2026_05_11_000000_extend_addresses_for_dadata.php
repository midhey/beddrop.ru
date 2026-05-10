<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->string('value')->nullable()->after('label');
            $table->string('unrestricted_value')->nullable()->after('value');
            $table->string('country')->nullable()->after('postal_code');
            $table->string('country_iso_code', 2)->nullable()->after('country');
            $table->string('federal_district')->nullable()->after('country_iso_code');

            $table->string('region_fias_id')->nullable()->after('federal_district');
            $table->string('region_kladr_id')->nullable()->after('region_fias_id');
            $table->string('region_iso_code')->nullable()->after('region_kladr_id');
            $table->string('region_with_type')->nullable()->after('region_iso_code');
            $table->string('region_type')->nullable()->after('region_with_type');
            $table->string('region_type_full')->nullable()->after('region_type');
            $table->string('region')->nullable()->after('region_type_full');

            $table->string('area_fias_id')->nullable()->after('region');
            $table->string('area_kladr_id')->nullable()->after('area_fias_id');
            $table->string('area_with_type')->nullable()->after('area_kladr_id');
            $table->string('area_type')->nullable()->after('area_with_type');
            $table->string('area_type_full')->nullable()->after('area_type');
            $table->string('area')->nullable()->after('area_type_full');

            $table->string('city_fias_id')->nullable()->after('area');
            $table->string('city_kladr_id')->nullable()->after('city_fias_id');
            $table->string('city_with_type')->nullable()->after('city_kladr_id');
            $table->string('city_type')->nullable()->after('city_with_type');
            $table->string('city_type_full')->nullable()->after('city_type');

            $table->string('settlement_fias_id')->nullable()->after('city');
            $table->string('settlement_kladr_id')->nullable()->after('settlement_fias_id');
            $table->string('settlement_with_type')->nullable()->after('settlement_kladr_id');
            $table->string('settlement_type')->nullable()->after('settlement_with_type');
            $table->string('settlement_type_full')->nullable()->after('settlement_type');
            $table->string('settlement')->nullable()->after('settlement_type_full');

            $table->string('street_fias_id')->nullable()->after('settlement');
            $table->string('street_kladr_id')->nullable()->after('street_fias_id');
            $table->string('street_with_type')->nullable()->after('street_kladr_id');
            $table->string('street_type')->nullable()->after('street_with_type');
            $table->string('street_type_full')->nullable()->after('street_type');
            $table->string('street')->nullable()->after('street_type_full');

            $table->string('house_fias_id')->nullable()->after('street');
            $table->string('house_kladr_id')->nullable()->after('house_fias_id');
            $table->string('house_type')->nullable()->after('house_kladr_id');
            $table->string('house_type_full')->nullable()->after('house_type');
            $table->string('house')->nullable()->after('house_type_full');

            $table->string('block_type')->nullable()->after('house');
            $table->string('block_type_full')->nullable()->after('block_type');
            $table->string('block')->nullable()->after('block_type_full');
            $table->string('flat_type')->nullable()->after('block');
            $table->string('flat_type_full')->nullable()->after('flat_type');
            $table->string('flat')->nullable()->after('flat_type_full');
            $table->string('entrance')->nullable()->after('flat');
            $table->string('floor')->nullable()->after('entrance');
            $table->string('intercom')->nullable()->after('floor');

            $table->string('fias_id')->nullable()->after('lng');
            $table->string('kladr_id')->nullable()->after('fias_id');
            $table->unsignedTinyInteger('qc_geo')->nullable()->after('kladr_id');
            $table->string('timezone')->nullable()->after('qc_geo');
            $table->string('beltway_hit')->nullable()->after('timezone');
            $table->decimal('beltway_distance', 8, 2)->nullable()->after('beltway_hit');
            $table->json('metro_json')->nullable()->after('beltway_distance');
            $table->json('raw_dadata_json')->nullable()->after('metro_json');
            $table->string('geo_source')->nullable()->after('raw_dadata_json');
            $table->timestamp('geocoded_at')->nullable()->after('geo_source');

            $table->index(['lat', 'lng']);
            $table->index('fias_id');
            $table->index('qc_geo');
        });
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropIndex(['lat', 'lng']);
            $table->dropIndex(['fias_id']);
            $table->dropIndex(['qc_geo']);

            $table->dropColumn([
                'value',
                'unrestricted_value',
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
            ]);
        });
    }
};
