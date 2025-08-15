<?php

namespace App\Models\Maileroo;

use Illuminate\Database\Eloquent\Model;
use App\Models\Maileroo\Recipient;
use App\Models\Maileroo\Message;

class MessageRecipient extends Model
{
    protected $table = 'messages_recipients';

    protected $fillable = [
        'messages_id',
        'recipient_id',
    ];

    public function message()
    {
        return $this->belongsTo(Message::class, 'messages_id');
    }

    public function recipient()
    {
        return $this->belongsTo(Recipient::class, 'recipient_id');
    }
}
