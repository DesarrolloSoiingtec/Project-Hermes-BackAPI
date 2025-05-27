<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientTrainingCourse extends Model
{
    protected $table = 'patients_training_courses';

    protected $fillable = [
        'training_course_id',
        'description',
        'date_appointment',
        'user_id',
        'state',
        'end_course',
        'patient_person_id',
        'commitment',
    ];

    public function trainingCourse()
    {
        return $this->belongsTo(TrainingCourse::class, 'training_course_id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_person_id');
    }
}
