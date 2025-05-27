<?php

namespace App\Models\Siau;

use Illuminate\Database\Eloquent\Model;

class patient_training_course extends Model
{
    protected $table = 'patients_training_courses';

    protected $fillable = [
        'training_course_id',
        'description',
        'date_appointment',
        'user_id',
        'state',
        'end_course',
        'attempts',
        'patient_person_id',
        'commitment',
        'reason_absence_id',
        'agreement_patient_id',
        'medical_id',
        'specialty_id',
        'service_id',
        'branch_id',
    ];

    // Relationships
    public function trainingCourse()
    {
        return $this->belongsTo(\App\Models\Siau\TrainingCourse::class, 'training_course_id');
    }

    public function patient()
    {
        return $this->belongsTo(\App\Models\Siau\Patient::class, 'patient_person_id');
    }

    public function agreementPatient()
    {
        return $this->belongsTo(\App\Models\Siau\AgreementPatient::class, 'agreement_patient_id');
    }
}
