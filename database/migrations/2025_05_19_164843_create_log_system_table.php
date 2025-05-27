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
        Schema::create('log_system', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->enum('level', ['debug','info','warning','error','critical'])
                  ->default('info');
            $table->string('message', 255);
            $table->json('context')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Índices para acelerar consultas
            $table->index('created_at');
            $table->index('level');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_system');
    }
};
