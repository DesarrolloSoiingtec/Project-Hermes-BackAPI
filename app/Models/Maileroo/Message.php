<?php

namespace App\Models\Maileroo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
     protected $fillable = [
        'message_id',
        'subject',
        'content',
        'date_message',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'date_message' => 'datetime',
    ];

    public function recipients(): BelongsToMany
    {
        return $this->belongsToMany(Recipient::class, 'messages_recipients');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
