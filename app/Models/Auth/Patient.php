<?php

namespace App\Models\Auth;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $table = 'patients';

    protected $fillable = [
        'id',
        'completed',
        'rh',
        'is_active',
    ];

    public function agreements()
    {
        return $this->hasMany(\App\Models\Siau\AgreementsPatients::class, 'patient_persons_id');
    }

    public function person()
    {
        return $this->belongsTo(Person::class, 'id', 'id');
    }
}

