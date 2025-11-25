<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('restaurant_id')
                ->constrained('restaurants')
                ->cascadeOnDelete();

            $table->foreignId('category_id')
                ->constrained('product_categories');

            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['restaurant_id', 'name']);
            $table->index('restaurant_id');
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
