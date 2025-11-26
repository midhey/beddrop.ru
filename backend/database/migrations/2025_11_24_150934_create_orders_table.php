<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('user_id')
                ->constrained('users');

            $table->foreignId('restaurant_id')
                ->constrained('restaurants');

            $table->unsignedBigInteger('courier_id')->nullable();

            $table->enum('status', [
                'CREATED',
                'ACCEPTED_BY_RESTAURANT',
                'COURIER_ASSIGNED',
                'PICKED_UP',
                'DELIVERED',
                'CANCELED_BY_USER',
                'CANCELED_BY_RESTAURANT',
            ])->default('CREATED');

            $table->enum('payment_status', [
                'PENDING',
                'AUTHORIZED',
                'PAID',
                'REFUNDED',
                'FAILED',
            ])->default('PENDING');

            $table->enum('payment_method', ['CASH', 'CARD', 'ONLINE'])->default('CASH');

            $table->decimal('total_price', 12, 2);
            $table->string('comment')->nullable();

            $table->foreignId('delivery_address_id')
                ->nullable()
                ->constrained('addresses')
                ->nullOnDelete();

            $table->decimal('delivery_lat', 9, 6)->nullable();
            $table->decimal('delivery_lng', 9, 6)->nullable();

            $table->timestamps();

            $table->foreign('courier_id')
                ->references('user_id')
                ->on('courier_profiles')
                ->nullOnDelete();

            $table->index('user_id');
            $table->index('restaurant_id');
            $table->index('courier_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
