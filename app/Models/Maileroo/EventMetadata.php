<?php

namespace App\Models\Maileroo;

use Illuminate\Database\Eloquent\Model;

class EventMetadata extends Model
{
    protected $fillable = [
        'event_id',
        'ip',
        'user_agent',
        'MTA',
        'created_at',
        'updated_at'
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
