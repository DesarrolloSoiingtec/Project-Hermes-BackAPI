<?php

namespace App\Models\Other;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $table = 'services';

    protected $fillable = [
        'name',
        'concept_service_id',
        'level',
        'gender',
        'min_age',
        'max_age',
        'cups',
        'is_active',
    ];

    public function conceptService()
    {
        return $this->belongsTo(\App\Models\Other\ConceptService::class, 'concept_service_id');
    }
}
