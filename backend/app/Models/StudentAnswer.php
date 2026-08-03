<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StudentAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_exam_id',
        'question_id',
        'answer',
        'is_correct',
        'marks_obtained',
    ];

    protected function casts(): array
    {
        return [
            'is_correct'     => 'boolean',
            'marks_obtained' => 'integer',
        ];
    }

    public function studentExam()
    {
        return $this->belongsTo(StudentExam::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
