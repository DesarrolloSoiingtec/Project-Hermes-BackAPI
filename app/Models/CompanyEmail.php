<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyEmail extends Model
{
    protected $table = 'company_emails';

    protected $fillable = [
        'email',
    ];
}
