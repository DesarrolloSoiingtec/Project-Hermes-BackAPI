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
        Schema::create('agreements', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('number');
            $table->string('type');
            $table->unsignedBigInteger('apb_id');
            $table->foreign('apb_id')->references('id')->on('apb');
            $table->string('reps_code');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('value_agreement');
            $table->boolean('is_active')->default(true);
            $table->string('description');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agreements');
    }
};

