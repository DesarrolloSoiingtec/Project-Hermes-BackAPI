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
        Schema::create('patients_training_courses',function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('training_course_id')->nullable();
            $table->foreign('training_course_id')->references('id')->on('training_courses');
            $table->string('description')->nullable();
            $table->date('date_appointment');
            $table->string('user_id');
            $table->integer('state')->default(1)->comment('1:Pendiente, 2:Proceso, 3:Culminado');
            $table->datetime('end_course')->nullable();
            $table->string('attempts')->nullable();
            $table->unsignedBigInteger('patient_person_id')->nullable();
            $table->foreign('patient_person_id')->references('id')->on('patients');
            $table->string('commitment')->nullable();
            $table->string('reason_absence_id')->nullable();
            $table->unsignedBigInteger('agreement_patient_id');
            $table->foreign('agreement_patient_id')->references('id')->on('agreements_patients');
            $table->string('timer')->nullable();
            $table->string('medical_id');
            $table->string('specialty_id');
            $table->string('service_id');
            $table->string('branch_id');

            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients_training_courses');
    }
};
