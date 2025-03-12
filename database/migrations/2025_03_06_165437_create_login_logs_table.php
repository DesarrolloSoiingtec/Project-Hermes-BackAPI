<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();
            // Relación con el usuario (puede ser nullable para log de intentos anónimos)
            $table->foreignId('user_id')->nullable()->constrained();
            // Almacena la dirección IP (45 caracteres para soportar IPv6)
            $table->string('ip_address', 45);
            // Guarda el agente de usuario (opcional)
            $table->text('browser')->nullable();
            // Timestamps de creación y actualización del registro
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('login_logs');
    }
};
