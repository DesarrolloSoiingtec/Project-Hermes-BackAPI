<?php

namespace App\Models\Maileroo;

use Illuminate\Database\Eloquent\Model;

class DocumentEmail extends Model
{
    protected $table = 'documents_emails';

    protected $fillable = [
        'emails_id',
        'documents_id',
    ];

    public function email()
    {
        return $this->belongsTo(Email::class, 'emails_id');
    }

    public function document()
    {
        return $this->belongsTo(Document::class, 'documents_id');
    }
}
