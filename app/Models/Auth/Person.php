<?php

namespace App\Models\Auth;

use Illuminate\Database\Eloquent\Model;

class Person extends Model
{
    protected $table = 'persons';

    protected $fillable = [
        'legal_document_type_id',
        'document_number',
        'email_patient',
        'name',
        'second_name',
        'lastname',
        'second_lastname',
        'address',
        'prefix_phone',
        'phone',
        'photo',
        'fingerprint',
        'birthday',
        'gender',
        'municipality',
        'department',
        'zone',
        'country_origin',
        'country_residence',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function patient()
    {
        return $this->hasOne(\App\Models\Siau\Patient::class, 'id', 'id');
    }
}
