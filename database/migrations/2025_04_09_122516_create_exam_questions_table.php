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
        Schema::create('exam_questions', function (Blueprint $table) {
            $table->id();
            $table->string('question', 255);
            $table->string('help_text', 255)->nullable();
            $table->boolean('multiple_answer')->default(false);
            $table->integer('value_question')->nullable();;
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('course_exams_id');
            $table->foreign('course_exams_id')->references('id')->on('course_exams');

            $table->string('video_ayuda')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_questions');
    }
};
