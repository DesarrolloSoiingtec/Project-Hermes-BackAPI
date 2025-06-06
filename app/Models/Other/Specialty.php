<?php

namespace App\Models\Other;

use Illuminate\Database\Eloquent\Model;

class Specialty extends Model
{
    protected $table = 'specialties';

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];
    public function subspecialties()
    {
        return $this->hasMany(SubSpecialty::class, 'specialty_id');
    }
}
