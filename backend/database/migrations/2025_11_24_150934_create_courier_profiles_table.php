<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');

            $table->enum('status', ['ACTIVE', 'SUSPENDED'])->default('ACTIVE');
            $table->enum('vehicle', ['FOOT', 'BIKE', 'SCOOTER', 'CAR'])->nullable();
            $table->decimal('rating', 3, 2)->nullable();

            $table->timestamps();

            $table->primary('user_id');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_profiles');
    }
};
