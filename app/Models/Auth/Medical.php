<?php

namespace App\Models\Auth;

use Illuminate\Database\Eloquent\Model;

class Medical extends Model
{
    protected $table = 'medicals';

    protected $fillable = [
        'id',
        'medical_record',
        'signature',
    ];

    public function person()
    {
        // Cambiamos la relación para usar 'id' en lugar de 'person_id'
        return $this->belongsTo(\App\Models\Auth\Person::class, 'id', 'id');
    }

    public function specialties()
    {
        return $this->hasMany(\App\Models\Other\MedicalSpecialty::class, 'medical_id', 'id');
    }
}
