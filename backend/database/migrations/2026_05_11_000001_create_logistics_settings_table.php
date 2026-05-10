<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logistics_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            $table->string('group')->default('general');
            $table->string('label');
            $table->text('description')->nullable();
            $table->json('validation_rules')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_admin_editable')->default(true);
            $table->timestamps();
        });

        $now = now();
        DB::table('logistics_settings')->insert([
            [
                'key' => 'delivery.base_price',
                'value' => '149',
                'type' => 'decimal',
                'group' => 'pricing',
                'label' => 'Базовая цена доставки',
                'description' => 'Фиксированная часть стоимости доставки.',
                'validation_rules' => json_encode(['required', 'numeric', 'min:0']),
                'sort_order' => 10,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'delivery.price_per_km',
                'value' => '30',
                'type' => 'decimal',
                'group' => 'pricing',
                'label' => 'Цена за километр',
                'description' => 'Переменная часть стоимости доставки за 1 км маршрута.',
                'validation_rules' => json_encode(['required', 'numeric', 'min:0']),
                'sort_order' => 20,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'delivery.service_fee',
                'value' => '39',
                'type' => 'decimal',
                'group' => 'pricing',
                'label' => 'Сервисный сбор',
                'description' => 'Дополнительный сбор платформы.',
                'validation_rules' => json_encode(['required', 'numeric', 'min:0']),
                'sort_order' => 30,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'delivery.buffer_time_min',
                'value' => '5',
                'type' => 'integer',
                'group' => 'time',
                'label' => 'Буфер доставки',
                'description' => 'Минуты, добавляемые к ETA для запаса.',
                'validation_rules' => json_encode(['required', 'integer', 'min:0']),
                'sort_order' => 40,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'delivery.pickup_buffer_time_min',
                'value' => '3',
                'type' => 'integer',
                'group' => 'time',
                'label' => 'Буфер выдачи заказа',
                'description' => 'Время на получение заказа курьером в ресторане.',
                'validation_rules' => json_encode(['required', 'integer', 'min:0']),
                'sort_order' => 50,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'delivery.default_prep_time_min',
                'value' => '20',
                'type' => 'integer',
                'group' => 'time',
                'label' => 'Время готовки по умолчанию',
                'description' => 'Используется, если ресторан не указал свое время приготовления.',
                'validation_rules' => json_encode(['required', 'integer', 'min:0']),
                'sort_order' => 60,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'valhalla.auto.shortest',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'valhalla_auto',
                'label' => 'Auto shortest',
                'description' => 'Использовать shortest для auto costing.',
                'validation_rules' => json_encode(['required', 'boolean']),
                'sort_order' => 100,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'valhalla.auto.use_highways',
                'value' => '0.5',
                'type' => 'decimal',
                'group' => 'valhalla_auto',
                'label' => 'Auto use_highways',
                'description' => 'Предпочтение автомагистралей в Valhalla.',
                'validation_rules' => json_encode(['required', 'numeric', 'between:0,1']),
                'sort_order' => 110,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'valhalla.auto.use_tolls',
                'value' => '0.1',
                'type' => 'decimal',
                'group' => 'valhalla_auto',
                'label' => 'Auto use_tolls',
                'description' => 'Предпочтение платных дорог.',
                'validation_rules' => json_encode(['required', 'numeric', 'between:0,1']),
                'sort_order' => 120,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'valhalla.auto.use_ferry',
                'value' => '0.2',
                'type' => 'decimal',
                'group' => 'valhalla_auto',
                'label' => 'Auto use_ferry',
                'description' => 'Предпочтение паромов.',
                'validation_rules' => json_encode(['required', 'numeric', 'between:0,1']),
                'sort_order' => 130,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'valhalla.auto.use_unpaved',
                'value' => '0.1',
                'type' => 'decimal',
                'group' => 'valhalla_auto',
                'label' => 'Auto use_unpaved',
                'description' => 'Предпочтение грунтовых дорог.',
                'validation_rules' => json_encode(['required', 'numeric', 'between:0,1']),
                'sort_order' => 140,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'valhalla.bicycle.use_roads',
                'value' => '0.4',
                'type' => 'decimal',
                'group' => 'valhalla_bicycle',
                'label' => 'Bicycle use_roads',
                'description' => 'Предпочтение дорог для велосипедной маршрутизации.',
                'validation_rules' => json_encode(['required', 'numeric', 'between:0,1']),
                'sort_order' => 200,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'valhalla.bicycle.use_hills',
                'value' => '0.5',
                'type' => 'decimal',
                'group' => 'valhalla_bicycle',
                'label' => 'Bicycle use_hills',
                'description' => 'Готовность использовать холмы для bicycle costing.',
                'validation_rules' => json_encode(['required', 'numeric', 'between:0,1']),
                'sort_order' => 210,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'valhalla.pedestrian.walking_speed',
                'value' => '5.1',
                'type' => 'decimal',
                'group' => 'valhalla_pedestrian',
                'label' => 'Pedestrian walking_speed',
                'description' => 'Скорость пешего курьера в км/ч.',
                'validation_rules' => json_encode(['required', 'numeric', 'min:1', 'max:10']),
                'sort_order' => 300,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('logistics_settings');
    }
};
