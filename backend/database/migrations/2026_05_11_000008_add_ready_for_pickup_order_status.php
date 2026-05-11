<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const STATUSES = [
        'CREATED',
        'ACCEPTED_BY_RESTAURANT',
        'READY_FOR_PICKUP',
        'COURIER_ASSIGNED',
        'PICKED_UP',
        'DELIVERED',
        'CANCELED_BY_USER',
        'CANCELED_BY_RESTAURANT',
    ];

    private const PREVIOUS_STATUSES = [
        'CREATED',
        'ACCEPTED_BY_RESTAURANT',
        'COURIER_ASSIGNED',
        'PICKED_UP',
        'DELIVERED',
        'CANCELED_BY_USER',
        'CANCELED_BY_RESTAURANT',
    ];

    public function up(): void
    {
        $this->replaceEnumValues(self::STATUSES);
    }

    public function down(): void
    {
        DB::table('orders')
            ->where('status', 'READY_FOR_PICKUP')
            ->update(['status' => 'ACCEPTED_BY_RESTAURANT']);

        DB::table('order_events')
            ->where('event', 'READY_FOR_PICKUP')
            ->delete();

        $this->replaceEnumValues(self::PREVIOUS_STATUSES);
    }

    /**
     * @param array<int, string> $values
     */
    private function replaceEnumValues(array $values): void
    {
        if (! Schema::hasTable('orders') || ! Schema::hasTable('order_events')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            $enum = implode(',', array_map(fn (string $value) => "'{$value}'", $values));

            DB::statement("ALTER TABLE orders MODIFY status ENUM({$enum}) NOT NULL DEFAULT 'CREATED'");
            DB::statement("ALTER TABLE order_events MODIFY event ENUM({$enum}) NOT NULL");
        }
    }
};
