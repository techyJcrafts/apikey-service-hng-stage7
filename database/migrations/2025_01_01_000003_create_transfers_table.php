<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_wallet_id')->constrained('wallets')->onDelete('cascade');
            $table->foreignId('receiver_wallet_id')->constrained('wallets')->onDelete('cascade');
            $table->decimal('amount', 20, 2);
            $table->string('reference', 50)->unique()->index();
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->foreignId('sender_transaction_id')->nullable()->constrained('transactions');
            $table->foreignId('receiver_transaction_id')->nullable()->constrained('transactions');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
