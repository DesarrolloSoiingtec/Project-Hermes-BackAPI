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
        Schema::create('ciuu_codes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('economic_activity_id'); // ID de la actividad económica
            $table->foreign('economic_activity_id')->references('id')->on('economic_activities');
            // Código de la actividad económica (Ej.: A, B, C, etc. o códigos numéricos según la clasificación)

            $table->string('division', 6); // Código de la división de la actividad económica (Ej.: 01, 02, 03, etc.)
            $table->string('group', 8); // Código del grupo de la actividad económica (Ej.: 011, 012, 013, etc.)
            $table->string('class', 10); // Código de la clase de la actividad económica (Ej.: 0111, 0112, 0113, etc.)
            $table->string('description', 255); // Descripción de la actividad económica
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ciuu_codes');
    }
};
