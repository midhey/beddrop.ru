<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('logistics_settings')->updateOrInsert(
            ['key' => 'delivery.service_commission_percent'],
            [
                'value' => '20',
                'type' => 'decimal',
                'group' => 'pricing',
                'label' => 'Комиссия сервиса с доставки, %',
                'description' => 'Процент стоимости доставки, который оставляет сервис. Остальное получает курьер.',
                'validation_rules' => json_encode(['required', 'numeric', 'min:0', 'max:100']),
                'sort_order' => 35,
                'is_admin_editable' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        DB::table('logistics_settings')
            ->where('key', 'delivery.service_commission_percent')
            ->delete();
    }
};
