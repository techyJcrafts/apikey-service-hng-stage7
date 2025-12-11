<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['deposit', 'transfer_in', 'transfer_out']);
            $table->decimal('amount', 20, 2); // Stored in Naira
            $table->decimal('balance_before', 20, 2);
            $table->decimal('balance_after', 20, 2);
            $table->string('reference', 50)->unique()->index();
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['wallet_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
