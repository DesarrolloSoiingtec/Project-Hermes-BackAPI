<?php

namespace App\Models\Other;

use Illuminate\Database\Eloquent\Model;

class CiuuCodes extends Model
{
    protected $table = 'ciuu_codes';

    protected $fillable = [
        'economic_activity_id',
        'division',
        'group',
        'class',
        'description',
    ];
}
