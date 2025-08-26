<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('emails', function (Blueprint $table) {
            // 1. Eliminar la llave foránea primero
            $table->dropForeign(['from']);

            // 2. Eliminar la columna actual
            $table->dropColumn('from');

            // 3. Crear la nueva columna como string
            $table->string('from')->nullable(); // o ->nullable() si necesitas que pueda ser null
        });
    }

    public function down()
    {
        Schema::table('emails', function (Blueprint $table) {
            // Revertir los cambios
            $table->dropColumn('from');
            $table->foreignId('from')->constrained('company_emails')->onDelete('cascade');
        });
    }
};
