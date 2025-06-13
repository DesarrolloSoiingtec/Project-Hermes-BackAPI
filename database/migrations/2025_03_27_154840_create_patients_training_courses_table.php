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

            $table->unsignedBigInteger('medical_id')->nullable();
            $table->foreign('medical_id')->references('id')->on('medicals');

            $table->unsignedBigInteger('specialty_id')->nullable();
            $table->foreign('specialty_id')->references('id')->on('specialties');

            $table->unsignedBigInteger('service_id')->nullable();
            $table->foreign('service_id')->references('id')->on('services');

            $table->unsignedBigInteger('branch_id')->nullable();
            $table->foreign('branch_id')->references('id')->on('branch');

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
