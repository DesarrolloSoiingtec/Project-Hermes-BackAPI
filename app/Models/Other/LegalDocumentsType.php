<?php

namespace App\Models\Other;

use Illuminate\Database\Eloquent\Model;

class LegalDocumentsType extends Model
{
    protected $table = 'legal_documents_types';

    protected $fillable = [
        'name',
        'code',
        'for_company',
        'is_active',
    ];
}
