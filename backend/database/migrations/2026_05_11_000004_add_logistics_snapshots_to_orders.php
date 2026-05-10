<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('delivery_distance_meters')->nullable()->after('delivery_lng');
            $table->unsignedInteger('delivery_duration_seconds')->nullable()->after('delivery_distance_meters');
            $table->decimal('delivery_price_snapshot', 12, 2)->nullable()->after('delivery_duration_seconds');
            $table->timestamp('estimated_pickup_at')->nullable()->after('delivery_price_snapshot');
            $table->timestamp('estimated_delivery_at')->nullable()->after('estimated_pickup_at');
            $table->json('logistics_snapshot_json')->nullable()->after('estimated_delivery_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_distance_meters',
                'delivery_duration_seconds',
                'delivery_price_snapshot',
                'estimated_pickup_at',
                'estimated_delivery_at',
                'logistics_snapshot_json',
            ]);
        });
    }
};
