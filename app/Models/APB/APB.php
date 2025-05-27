<?php

namespace App\Models\APB;

use Illuminate\Database\Eloquent\Model;

class APB extends Model
{
    protected $table = 'apb';

    protected $fillable = [
        'name',
        'company_type_id',
        'number',
        'verification_digit',
        'address',
        'phone',
        'website',
        'billing_email',
        'manager_name',
        'is_active',
    ];

    public function companyType()
    {
        return $this->belongsTo(\App\Models\LegalDocumentsType::class, 'company_type_id');
    }

    public function agreements()
    {
        return $this->hasMany(Agreement::class, 'apb_id');
    }
}
