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
        Schema::table('documents_emails', function (Blueprint $table) {
            // Eliminar la restricción única si existe
            $table->dropUnique(['emails_id', 'documents_id']);
        });
    }

    public function down(): void
    {
        Schema::table('documents_emails', function (Blueprint $table) {
            // Restaurar la restricción única
            $table->unique(['emails_id', 'documents_id']);
        });
    }
};
