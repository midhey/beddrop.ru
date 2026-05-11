<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->boolean('accepts_orders')->default(true)->after('is_active');
            $table->string('timezone')->default('Europe/Moscow')->after('accepts_orders');
            $table->time('opens_at')->nullable()->after('timezone');
            $table->time('closes_at')->nullable()->after('opens_at');
            $table->string('closed_reason')->nullable()->after('closes_at');
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn([
                'accepts_orders',
                'timezone',
                'opens_at',
                'closes_at',
                'closed_reason',
            ]);
        });
    }
};
