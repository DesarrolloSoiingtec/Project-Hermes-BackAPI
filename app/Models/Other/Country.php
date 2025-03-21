<?php

namespace App\Models\Other;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $fillable = [
        'name',
        'iso2',
        'prefix',
    ];
}
