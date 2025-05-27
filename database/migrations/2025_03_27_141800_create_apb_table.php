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
        Schema::create('apb', function (Blueprint $table) {
            $table->id();

            $table->string('name',);
            $table->unsignedBigInteger('company_type_id');
            $table->foreign('company_type_id')->references('id')->on('legal_documents_types');
            $table->string('number')->unique();
            $table->string('verification_digit', 2);
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->string('billing_email')->nullable();
            $table->string('manager_name')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apb');
    }
};
