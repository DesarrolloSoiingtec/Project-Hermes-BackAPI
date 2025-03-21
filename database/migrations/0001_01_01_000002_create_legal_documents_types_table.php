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
        Schema::create('legal_documents_types', function (Blueprint $table) {
            $table->id();

            $table->string('name', 255);
            $table->string('code', 12);
            $table->boolean('for_company')->nullable(); // Si es un documento para la empresa (V) o para usuario (F)
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legal_documents_types');
    }
};
