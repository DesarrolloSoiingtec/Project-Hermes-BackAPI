<?php

namespace App\Models\Siau;

use Illuminate\Database\Eloquent\Model;

class ExamAnswer extends Model
{
    protected $table = 'exam_answers';

    protected $fillable = [
        'answer',
        'is_correct',
        'is_active',
        'exam_question_id',
        'help_video',
    ];

    public function examQuestion()
    {
        return $this->belongsTo(ExamQuestion::class, 'exam_question_id');
    }
}
