<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Result;
use App\Models\StudentAnswer;
use App\Models\StudentExam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExamAttemptController extends Controller
{
    /**
     * Start an exam.
     * Returns a detailed error message for every validation failure.
     */
    public function start(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|integer|exists:exams,id',
        ]);

        $user    = $request->user();
        $student = $user->student;

        // ── Student profile check ──────────────────────────────────────────
        if (! $student) {
            return response()->json([
                'message' => 'Student profile not found. Please contact your administrator.',
            ], 403);
        }

        // ── Load exam ─────────────────────────────────────────────────────
        $exam = Exam::with(['questions' => function ($q) {
            $q->orderBy('order');
        }, 'course'])->find((int) $request->exam_id);

        if (! $exam) {
            return response()->json(['message' => 'Exam not found.'], 404);
        }

        // ── Status check ──────────────────────────────────────────────────
        if ($exam->status === 'draft') {
            return response()->json([
                'message' => 'This exam is not published yet.',
            ], 403);
        }

        if ($exam->status === 'completed') {
            return response()->json([
                'message' => 'This exam has already ended.',
            ], 403);
        }

        if ($exam->status !== 'active') {
            return response()->json([
                'message' => 'This exam is not currently available.',
            ], 403);
        }

        // ── Time window check ─────────────────────────────────────────────
        $now = now();

        // Convert dates to same timezone to avoid comparison issues
        $startDate = $exam->start_date->setTimezone(config('app.timezone'));
        $endDate   = $exam->end_date->setTimezone(config('app.timezone'));
        $nowLocal  = $now->setTimezone(config('app.timezone'));

        if ($nowLocal->lt($startDate)) {
            return response()->json([
                'message' => 'This exam has not started yet. It opens at ' . $startDate->format('M d, Y H:i') . '.',
            ], 403);
        }

        if ($nowLocal->gt($endDate)) {
            return response()->json([
                'message' => 'This exam has already ended. It closed at ' . $endDate->format('M d, Y H:i') . '.',
            ], 403);
        }

        // ── Questions check ───────────────────────────────────────────────
        if ($exam->questions->isEmpty()) {
            return response()->json([
                'message' => 'This exam has no questions yet. Please contact your instructor.',
            ], 403);
        }

        // ── Existing attempt check ────────────────────────────────────────
        $existing = StudentExam::where('student_id', $student->id)
            ->where('exam_id', $exam->id)
            ->first();

        if ($existing) {
            if (in_array($existing->status, ['submitted', 'graded'])) {
                return response()->json([
                    'message' => 'You have already completed this exam.',
                ], 403);
            }

            // Resume in-progress attempt
            $questions = $exam->shuffle_questions
                ? $exam->questions->shuffle()
                : $exam->questions;

            $questions->makeHidden(['correct_answer']);

            Log::info("Student {$student->id} resumed exam {$exam->id}");

            return response()->json([
                'student_exam'   => $existing,
                'questions'      => $questions->values(),
                'remaining_time' => $existing->remaining_time,
            ]);
        }

        // ── Create new attempt ────────────────────────────────────────────
        $studentExam = StudentExam::create([
            'student_id' => $student->id,
            'exam_id'    => $exam->id,
            'started_at' => now(),
            'status'     => 'in_progress',
        ]);

        $questions = $exam->shuffle_questions
            ? $exam->questions->shuffle()
            : $exam->questions;

        $questions->makeHidden(['correct_answer']);

        Log::info("Student {$student->id} started exam {$exam->id}");

        return response()->json([
            'student_exam'   => $studentExam,
            'questions'      => $questions->values(),
            'remaining_time' => $exam->duration * 60,
        ], 201);
    }

    /**
     * Submit answers and auto-grade objective questions.
     */
    public function submit(Request $request)
    {
        $request->validate([
            'student_exam_id'           => 'required|integer|exists:student_exams,id',
            'answers'                   => 'required|array',
            'answers.*.question_id'     => 'required|integer|exists:questions,id',
            'answers.*.answer'          => 'nullable|string|max:5000',
        ]);

        $user        = $request->user();
        $studentExam = StudentExam::with(['exam.questions', 'student'])
            ->findOrFail((int) $request->student_exam_id);

        // Ownership check
        if ($studentExam->student->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Idempotency — already submitted
        if (in_array($studentExam->status, ['submitted', 'graded'])) {
            $result = Result::where('student_exam_id', $studentExam->id)->first();
            return response()->json([
                'message' => 'Exam already submitted.',
                'result'  => $result ? $result->load(['exam', 'student.user']) : null,
            ]);
        }

        $result = DB::transaction(function () use ($request, $studentExam) {
            $exam          = $studentExam->exam;
            $questionsMap  = $exam->questions->keyBy('id');
            $obtainedMarks = 0;

            // Delete any draft answers before saving final ones
            $studentExam->answers()->delete();

            foreach ($request->answers as $answerData) {
                $question = $questionsMap->get($answerData['question_id']);
                if (! $question) continue;

                $isCorrect     = null;
                $marksObtained = 0;

                // Auto-grade objective questions only
                if ($question->question_type !== 'short_answer') {
                    $isCorrect     = $question->checkAnswer($answerData['answer'] ?? '');
                    $marksObtained = $isCorrect ? $question->marks : 0;
                }

                $obtainedMarks += $marksObtained;

                StudentAnswer::create([
                    'student_exam_id' => $studentExam->id,
                    'question_id'     => $question->id,
                    'answer'          => $answerData['answer'] ?? null,
                    'is_correct'      => $isCorrect,
                    'marks_obtained'  => $marksObtained,
                ]);
            }

            // Mark attempt as graded
            $studentExam->update([
                'submitted_at' => now(),
                'status'       => 'graded',
            ]);

            // Recalculate total_marks from questions (in case it was 0)
            $totalMarks = $exam->questions->sum('marks') ?: $exam->total_marks ?: 1;
            $percentage = round(($obtainedMarks / $totalMarks) * 100, 2);
            $isPassed   = $obtainedMarks >= $exam->passing_marks;

            // Check for short_answer questions that need manual grading
            $hasEssay  = $exam->questions->where('question_type', 'short_answer')->isNotEmpty();
            $remarks   = $isPassed ? 'Passed' : 'Failed';
            if ($hasEssay) {
                $remarks .= ' (Short answer questions are pending manual review)';
            }

            return Result::create([
                'student_exam_id' => $studentExam->id,
                'student_id'      => $studentExam->student_id,
                'exam_id'         => $exam->id,
                'total_marks'     => $totalMarks,
                'obtained_marks'  => $obtainedMarks,
                'percentage'      => $percentage,
                'is_passed'       => $isPassed,
                'remarks'         => $remarks,
            ]);
        });

        Log::info("Student {$studentExam->student_id} submitted exam {$studentExam->exam_id}. Score: {$result->percentage}%");

        return response()->json([
            'message' => 'Exam submitted successfully.',
            'result'  => $result->load(['exam', 'student.user']),
        ]);
    }

    /**
     * Auto-save draft answers every 30 seconds.
     */
    public function autoSave(Request $request)
    {
        $request->validate([
            'student_exam_id'        => 'required|integer|exists:student_exams,id',
            'answers'                => 'required|array',
            'answers.*.question_id'  => 'required|integer|exists:questions,id',
            'answers.*.answer'       => 'nullable|string|max:5000',
        ]);

        $studentExam = StudentExam::with('student')->findOrFail((int) $request->student_exam_id);

        if ($studentExam->student->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if (in_array($studentExam->status, ['submitted', 'graded'])) {
            return response()->json(['message' => 'Exam already submitted.'], 422);
        }

        DB::transaction(function () use ($request, $studentExam) {
            foreach ($request->answers as $a) {
                if (empty($a['answer'])) continue; // Don't save empty answers
                StudentAnswer::updateOrCreate(
                    [
                        'student_exam_id' => $studentExam->id,
                        'question_id'     => (int) $a['question_id'],
                    ],
                    [
                        'answer'         => $a['answer'],
                        'is_correct'     => null,
                        'marks_obtained' => 0,
                    ]
                );
            }
        });

        return response()->json([
            'message'  => 'Answers saved.',
            'saved_at' => now()->toISOString(),
        ]);
    }

    /**
     * Restore saved draft answers when student resumes exam.
     */
    public function loadSaved(Request $request, $studentExamId)
    {
        $studentExam = StudentExam::with(['student', 'answers'])
            ->findOrFail((int) $studentExamId);

        if ($studentExam->student->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Map question_id => answer string
        $saved = $studentExam->answers->mapWithKeys(fn($a) => [
            (string) $a->question_id => $a->answer,
        ]);

        return response()->json([
            'student_exam'   => $studentExam,
            'saved_answers'  => $saved,
            'remaining_time' => $studentExam->remaining_time,
        ]);
    }
}
