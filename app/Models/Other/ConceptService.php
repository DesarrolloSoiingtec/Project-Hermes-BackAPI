<?php

namespace App\Models\Other;

use Illuminate\Database\Eloquent\Model;

class ConceptService extends Model
{
    protected $table = 'concepts_services';

    protected $fillable = [
        'code',
        'name',
        'is_active',
    ];
}
