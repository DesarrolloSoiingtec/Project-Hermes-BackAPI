<?php

namespace App\Models\Siau;

use Illuminate\Database\Eloquent\Model;

class TrainingCourse extends Model
{
    protected $table = 'training_courses';

    protected $fillable = [
        'name',
        'description',
        'help_video',
        'help_sound',
        'stopwatch',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
