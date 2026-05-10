<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_route_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('segment_type');
            $table->string('mode')->default('auto');
            $table->unsignedInteger('distance_meters')->default(0);
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->longText('encoded_shape')->nullable();
            $table->json('raw_response_json')->nullable();
            $table->json('settings_snapshot_json')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'segment_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_route_segments');
    }
};
