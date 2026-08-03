<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'course_id',
        'created_by',
        'start_date',
        'end_date',
        'duration',
        'total_marks',
        'passing_marks',
        'status',
        'shuffle_questions',
    ];

    protected function casts(): array
    {
        return [
            'start_date'        => 'datetime',
            'end_date'          => 'datetime',
            'shuffle_questions' => 'boolean',
        ];
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions()
    {
        return $this->hasMany(Question::class)->orderBy('order');
    }

    public function studentExams()
    {
        return $this->hasMany(StudentExam::class);
    }

    public function results()
    {
        return $this->hasMany(Result::class);
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'student_exams')
                    ->withPivot(['started_at', 'submitted_at', 'status'])
                    ->withTimestamps();
    }

    // Auto-calculate total marks from questions
    public function recalculateTotalMarks(): void
    {
        $this->total_marks = $this->questions()->sum('marks');
        $this->save();
    }
}
