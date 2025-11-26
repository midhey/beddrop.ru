<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_shifts', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('courier_user_id');

            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('ended_at')->nullable();
            $table->string('status')->default('OPEN');

            $table->timestamps();

            $table->foreign('courier_user_id')
                ->references('user_id')
                ->on('courier_profiles')
                ->cascadeOnDelete();

            $table->index(['courier_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_shifts');
    }
};
