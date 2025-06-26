<?php

namespace App\Models\Other;

use Illuminate\Database\Eloquent\Model;

class CiuuCodeCompany extends Model
{
    protected $table = 'ciuu_codes_companies';

    protected $fillable = [
        'company_id',
        'ciuu_code_id',
    ];

    /**
     * Obtiene la empresa asociada a este registro.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Obtiene el código CIUU asociado a este registro.
     */
    public function ciuuCode()
    {
        return $this->belongsTo(CiuuCodes::class, 'ciuu_code_id');
    }
}
