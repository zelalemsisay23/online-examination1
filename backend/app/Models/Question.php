<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'question_text',
        'question_type',
        'options',
        'correct_answer',
        'marks',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'marks'   => 'integer',
            'order'   => 'integer',
        ];
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function studentAnswers()
    {
        return $this->hasMany(StudentAnswer::class);
    }

    /**
     * Check if a given answer is correct for objective questions.
     */
    public function checkAnswer(string $answer): bool
    {
        if ($this->question_type === 'short_answer') {
            return false; // Short answers need manual grading
        }

        return strtolower(trim($answer)) === strtolower(trim($this->correct_answer));
    }
}
