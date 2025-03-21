<?php

namespace App\Models\Other;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $table = 'companies';

    protected $fillable = [
        'name',
        'legal_name',
        'nit',
        'company_type',
        'legal_representative',
        'incorporation_date',
        'economic_activity',
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
        'registration_number',
        'verification_digit'
    ];
}
