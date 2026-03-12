<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gateways', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('driver');  // Classe PHP do adapter: Gateway1Adapter, Gateway2Adapter
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('priority')->default(1);
            $table->text('credentials')->nullable(); // JSON criptografado com as credenciais dos gateways
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gateways');
    }
};
