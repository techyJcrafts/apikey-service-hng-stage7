<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name', 100);
            $table->string('key', 64)->unique()->index(); // SHA-256 hash (64 chars)
            $table->json('permissions'); // ["deposit", "transfer", "read"]
            $table->timestamp('expires_at')->index()->nullable(); // Nullable for backward compat if needed, or make sure to fill it
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['user_id', 'revoked_at', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
