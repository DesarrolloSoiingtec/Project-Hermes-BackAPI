<?php

namespace App\Models\Other;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $table = 'branch';

    protected $fillable = [
        'name',
        'address',
        'city',
        'state',
        'country',
        'phone',
        'email',
        'manager_name',
        'is_active',
    ];
}
