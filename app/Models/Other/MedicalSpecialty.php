<?php

namespace App\Models\Other;

use Illuminate\Database\Eloquent\Model;


class MedicalSpecialty extends Model
{
    protected $table = 'specialties_medicals';

    protected $fillable = [
        'medical_id',
        'specialty_id',
    ];

    public function medical()
    {
        return $this->belongsTo(\App\Models\Auth\Medical::class, 'medical_id', 'id');
    }

    public function specialty()
    {
        return $this->belongsTo(\App\Models\Other\Specialty::class, 'specialty_id');
    }
}
