<?php

namespace App\Models\Other;

use Illuminate\Database\Eloquent\Model;

class EconomicActivity extends Model
{
    protected $table = 'economic_activities';

    protected $fillable = [
        'section',
        'activity_name',
        'description',
    ];
}
