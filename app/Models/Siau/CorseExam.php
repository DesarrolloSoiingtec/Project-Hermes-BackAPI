<?php

namespace App\Models\Siau;

use Illuminate\Database\Eloquent\Model;

class CorseExam extends Model
{
    protected $table = 'course_exams';

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'training_course_id',
        'video_ayuda',
    ];

    public function trainingCourse()
    {
        return $this->belongsTo(TrainingCourse::class, 'training_course_id');
    }
}
