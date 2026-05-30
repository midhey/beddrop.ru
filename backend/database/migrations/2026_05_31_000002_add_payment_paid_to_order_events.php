<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("
            ALTER TABLE order_events
            MODIFY event ENUM(
                'CREATED',
                'ACCEPTED_BY_RESTAURANT',
                'READY_FOR_PICKUP',
                'COURIER_ASSIGNED',
                'PICKED_UP',
                'DELIVERED',
                'CANCELED_BY_USER',
                'CANCELED_BY_RESTAURANT',
                'PAYMENT_PAID'
            ) NOT NULL
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("
            ALTER TABLE order_events
            MODIFY event ENUM(
                'CREATED',
                'ACCEPTED_BY_RESTAURANT',
                'READY_FOR_PICKUP',
                'COURIER_ASSIGNED',
                'PICKED_UP',
                'DELIVERED',
                'CANCELED_BY_USER',
                'CANCELED_BY_RESTAURANT'
            ) NOT NULL
        ");
    }
};
