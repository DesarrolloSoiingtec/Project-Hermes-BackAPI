<?php

namespace App\Models\Siau;

use Illuminate\Database\Eloquent\Model;

class CourseFile extends Model
{
    protected $table = 'course_files';

    protected $fillable = [
        'path',
        'name',
        'training_course_id',
        'is_active',
    ];

    public function trainingCourse()
    {
        return $this->belongsTo(TrainingCourse::class, 'training_course_id');
    }
}
