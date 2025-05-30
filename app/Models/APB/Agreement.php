<?php

namespace App\Models\APB;

use Illuminate\Database\Eloquent\Model;

class Agreement extends Model
{
    protected $table = 'agreements';

    protected $fillable = [
        'name',
        'number',
        'type',
        'apb_id',
        'reps_code',
        'start_date',
        'end_date',
        'value_agreement',
        'description',
        'is_active',
        'contracting_modality',
        'contracted_services',
        'complexity_level',
        'billing_periodicity',
    ];

    public function apb()
    {
        return $this->belongsTo(\App\Models\APB\APB::class, 'apb_id');
    }
}
