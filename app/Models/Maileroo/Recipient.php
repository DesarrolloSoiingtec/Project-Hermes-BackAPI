<?php

namespace App\Models\Maileroo;

use Illuminate\Database\Eloquent\Model;

class Recipient extends Model
{
    protected $fillable = [
        'email',
        'created_at',
        'updated_at'
    ];

    public function messages(): BelongsToMany
    {
        return $this->belongsToMany(Message::class, 'messages_recipients');
    }
}
