<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->foreignId('media_id')
                ->constrained('media')
                ->cascadeOnDelete();

            $table->integer('sort_order')->default(0);
            $table->boolean('is_cover')->default(false);

            $table->timestamps();

            $table->index(['product_id', 'sort_order']);
            $table->unique(['product_id', 'media_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
