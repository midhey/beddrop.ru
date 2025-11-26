<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_events', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            $table->enum('event', [
                'CREATED',
                'ACCEPTED_BY_RESTAURANT',
                'COURIER_ASSIGNED',
                'PICKED_UP',
                'DELIVERED',
                'CANCELED_BY_USER',
                'CANCELED_BY_RESTAURANT',
            ]);

            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_events');
    }
};
