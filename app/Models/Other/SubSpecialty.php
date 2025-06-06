<?php

namespace App\Models\Other;

use Illuminate\Database\Eloquent\Model;

class SubSpecialty extends Model
{
    protected $table = 'subspecialty';

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'specialty_id'
    ];

    public function specialty()
    {
        return $this->belongsTo(Specialty::class, 'specialty_id');
    }
}
