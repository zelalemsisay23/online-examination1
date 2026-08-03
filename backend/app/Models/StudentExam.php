<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StudentExam extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'exam_id',
        'started_at',
        'submitted_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'started_at'   => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function answers()
    {
        return $this->hasMany(StudentAnswer::class);
    }

    public function result()
    {
        return $this->hasOne(Result::class);
    }

    /**
     * Calculate remaining time in seconds.
     */
    public function getRemainingTimeAttribute(): int
    {
        if (!$this->started_at) return 0;

        $elapsed  = now()->diffInSeconds($this->started_at);
        $duration = $this->exam->duration * 60;

        return max(0, $duration - $elapsed);
    }
}
