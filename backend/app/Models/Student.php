<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'student_id',
        'phone',
        'address',
        'date_of_birth',
        'gender',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function studentExams()
    {
        return $this->hasMany(StudentExam::class);
    }

    public function results()
    {
        return $this->hasMany(Result::class);
    }

    public function exams()
    {
        return $this->belongsToMany(Exam::class, 'student_exams')
                    ->withPivot(['started_at', 'submitted_at', 'status'])
                    ->withTimestamps();
    }
}
