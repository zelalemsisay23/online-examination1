<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Result extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_exam_id',
        'student_id',
        'exam_id',
        'total_marks',
        'obtained_marks',
        'percentage',
        'is_passed',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'percentage' => 'decimal:2',
            'is_passed'  => 'boolean',
        ];
    }

    public function studentExam()
    {
        return $this->belongsTo(StudentExam::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }
}
