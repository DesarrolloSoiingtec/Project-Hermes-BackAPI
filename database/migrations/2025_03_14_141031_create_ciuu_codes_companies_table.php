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
        Schema::create('ciuu_codes_companies', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id');
            $table->foreign('company_id')->references('id')->on('companies');  // ID de la empresa

            $table->unsignedBigInteger('ciuu_code_id');
            $table->foreign('ciuu_code_id')->references('id')->on('ciuu_codes');  // ID de ciuu junto a el tipo
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ciuu_codes_companies');
    }
};
