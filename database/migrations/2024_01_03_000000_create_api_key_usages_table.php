<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_key_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_key_id')->constrained()->onDelete('cascade');
            $table->string('method');
            $table->string('endpoint');
            $table->string('ip_address')->nullable();
            $table->integer('status_code');
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_key_usages');
    }
};
