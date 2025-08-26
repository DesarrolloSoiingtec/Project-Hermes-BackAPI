<?php

namespace App\Models\Maileroo;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $table = 'documents';

    protected $fillable = [
        'size',
        'type',
        'path',
    ];

    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

    protected $casts = [
        'id' => 'integer',
        'size' => 'string',
        'type' => 'string',
        'path' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
