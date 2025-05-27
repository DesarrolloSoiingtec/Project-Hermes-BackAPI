<?php

namespace App\Models\Course;

use Illuminate\Database\Eloquent\Model;

class ExamQuestions extends Model
{
    protected $table = 'exam_questions';

    protected $fillable = [
        'question',
        'help_text',
        'multiple_answer',
        'value_question',
        'is_active',
        'course_exams_id',
        'video_ayuda',
    ];

    public function courseExam()
    {
        return $this->belongsTo(\App\Models\Course\CourseExams::class, 'course_exams_id');
    }
}
