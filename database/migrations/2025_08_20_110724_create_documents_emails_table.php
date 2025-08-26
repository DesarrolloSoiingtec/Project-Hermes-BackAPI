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
        Schema::create('documents_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emails_id')->constrained('emails')->onDelete('cascade');
            $table->foreignId('documents_id')->constrained('documents')->onDelete('cascade');
            $table->timestamps();

            // Índices para mejorar rendimiento en consultas
            $table->index(['emails_id', 'documents_id']);
            $table->index('emails_id');
            $table->index('documents_id');

            // Evitar duplicados en la relación many-to-many
            $table->unique(['emails_id', 'documents_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents_emails');
    }
};
