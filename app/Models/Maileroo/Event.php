<?php

namespace App\Models\Maileroo;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'external_event_id',
        'event_type',
        'timestamp',
        'inserted_at',
        'reject_reason',
        'domain_id',
        'user_id',
        'message_id'
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'inserted_at' => 'datetime',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function eventMetadata(): HasMany
    {
        return $this->hasMany(EventMetadata::class);
    }
}
