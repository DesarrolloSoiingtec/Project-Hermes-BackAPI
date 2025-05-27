<?php

namespace App\Models\Siau;

use Illuminate\Database\Eloquent\Model;

class ReasonAbsence extends Model
{
    protected $table = 'reasons_absences';

    protected $fillable = [
        'name',
        'is_active',
    ];
}
