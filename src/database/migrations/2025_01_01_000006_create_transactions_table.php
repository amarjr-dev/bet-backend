<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->foreignId('gateway_id')->constrained('gateways')->restrictOnDelete();
            $table->string('external_id')->nullable(); // ID retornado pelo gateway
            $table->enum('status', ['pending', 'approved', 'refunded', 'failed'])->default('pending');
            $table->unsignedBigInteger('amount'); // valor total em centavos
            $table->string('card_last_numbers', 4); // apenas últimos 4 dígitos
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
