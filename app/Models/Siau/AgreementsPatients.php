<?php

namespace App\Models\Siau;

use Illuminate\Database\Eloquent\Model;

class AgreementsPatients extends Model
{
    protected $table = 'agreements_patients';

    protected $fillable = [
        'patient_persons_id',
        'agreement_id',
    ];

    public function patient()
    {
        return $this->belongsTo(\App\Models\Siau\Patient::class, 'patient_persons_id');
    }

    public function agreement()
    {
        return $this->belongsTo(\App\Models\Siau\Agreement::class, 'agreement_id');
    }
}
