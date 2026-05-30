<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->string('provider')->default('yookassa');
            $table->string('provider_payment_id')->nullable()->unique();
            $table->string('provider_status')->nullable();
            $table->string('confirmation_url', 2048)->nullable();
            $table->decimal('amount_value', 12, 2);
            $table->string('currency', 3);
            $table->string('idempotency_key')->unique();
            $table->json('raw_payload')->nullable();
            $table->timestamp('provider_created_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['provider', 'provider_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
