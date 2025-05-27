<?php

namespace App\Models\Siau;

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
}
