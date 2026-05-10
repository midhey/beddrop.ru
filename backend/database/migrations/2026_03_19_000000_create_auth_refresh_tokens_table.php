<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_refresh_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('family_id');
            $table->foreignId('rotated_from_id')->nullable()->constrained('auth_refresh_tokens')->nullOnDelete();
            $table->string('client_type', 20);
            $table->string('token_hash', 64)->unique();
            $table->string('device_name')->nullable();
            $table->string('user_agent', 1000)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->string('revoked_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'client_type']);
            $table->index('family_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_refresh_tokens');
    }
};
