<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurants', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('slug')->unique();

            $table->foreignId('address_id')
                ->nullable()
                ->constrained('addresses')
                ->nullOnDelete();

            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('prep_time_min')->nullable();
            $table->unsignedInteger('prep_time_max')->nullable();

            $table->foreignId('logo_media_id')
                ->nullable()
                ->constrained('media')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};
