<?php

namespace App\Models\Maileroo;

use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    protected $fillable = [
        'from',
        'domain_company_id', // Asegurarnos que coincida con la columna en el controlador
        'to',
        'cc',
        'bcc',
        'subject',
        'content',
        'scheduled_time',
    ];

    protected $casts = [
        'to' => 'array',
        'cc' => 'array',
        'bcc' => 'array',
        'scheduled_time' => 'datetime'
    ];

    public function sender()
    {
        return $this->belongsTo(\App\Models\Maileroo\CompanyEmail::class, 'from');
    }

    public function domain()
    {
        return $this->belongsTo(\App\Models\Domain::class, 'domain_company_id');
    }

    public function recipient()
    {
        return $this->belongsTo(\App\Models\Maileroo\Recipient::class, 'to');
    }

    public function ccRecipient()
    {
        return $this->belongsTo(\App\Models\Maileroo\Recipient::class, 'cc');
    }

    public function ccoRecipient()
    {
        return $this->belongsTo(\App\Models\Maileroo\Recipient::class, 'cco');
    }
}
