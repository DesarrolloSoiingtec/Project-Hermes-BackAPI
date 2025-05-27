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
        Schema::create('exam_answers', function (Blueprint $table) {
            $table->id();

            $table->string('answer', 255);
            $table->boolean('is_correct')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('exam_question_id');
            $table->foreign('exam_question_id')->references('id')->on('exam_questions');
            $table->string('help_video', 45)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_answers');
    }
};
