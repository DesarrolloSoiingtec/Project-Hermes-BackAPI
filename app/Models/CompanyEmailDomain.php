<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyEmailDomain extends Model
{
    protected $table = 'company_emails_domain';

    protected $fillable = [
        'company_email_id',
        'domain_id',
    ];

    public function companyEmail(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(CompanyEmail::class, 'company_email_id');
    }

    public function domain(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Domain::class, 'domain_id');
    }
}
