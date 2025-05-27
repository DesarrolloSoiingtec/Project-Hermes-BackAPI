<?php

namespace App\Models\APB;

use Illuminate\Database\Eloquent\Model;

class AgreementPatient extends Model
{
    protected $table = 'agreements_patients';

    protected $fillable = [
        'patient_persons_id',
        'agreement_id',
    ];

    public function patient()
    {
        return $this->belongsTo(\App\Models\APB\Patient::class, 'patient_persons_id');
    }

    public function agreement()
    {
        return $this->belongsTo(\App\Models\APB\Agreement::class, 'agreement_id');
    }
}
