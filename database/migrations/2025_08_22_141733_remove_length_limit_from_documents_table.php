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
        Schema::table('documents', function (Blueprint $table) {
            // Cambiar los campos para quitar la limitación de 45 caracteres
            $table->string('size')->change();
            $table->string('type')->change();
            $table->string('path')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // Restaurar las limitaciones originales
            $table->string('size', 45)->change();
            $table->string('type', 45)->change();
            $table->string('path', 45)->change();
        });
    }
};
