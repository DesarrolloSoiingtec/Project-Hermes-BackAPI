<?php

namespace App\Models\Other;

use Illuminate\Database\Eloquent\Model;


class Company extends Model
{
    protected $table = 'companies';

    protected $casts = [
        'economic_activities' => 'array',
    ];

    protected $fillable = [
        'name',
        'legal_name',
        'id_number',
        'verification_digit',
        'company_type_id', // <- Asegúrate de que esté aquí
        'legal_representative',
        'incorporation_date',
        'street',
        'exterior_number',
        'interior_number',
        'neighborhood',
        'city',
        'municipality',
        'department',
        'postal_code',
        'phone',
        'prefix_phone',
        'email',
        'website',
        'user_contact_phone',
        'user_prefix_phone',
        'user_contact_email',
        'registration_number'
    ];

    public function companyType()
    {
        return $this->belongsTo(\App\Models\Other\LegalDocumentsType::class, 'company_type_id');
    }
}
