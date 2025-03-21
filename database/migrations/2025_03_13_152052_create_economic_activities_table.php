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
        Schema::create('economic_activities', function (Blueprint $table) {
            $table->id();

            $table->string('section', 2); // Código de la actividad económica (Ej.: A, B, C, etc. o códigos numéricos según la clasificación)
            $table->string('activity_name', 255); // Nombre de la actividad económica (Ej.: Agricultura, Minería, etc.)
            $table->text('description', 255)->nullable(); // Descripción detallada de la actividad económica

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('economic_activities');
    }
};
