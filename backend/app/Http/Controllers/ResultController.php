<?php

namespace App\Http\Controllers;

use App\Models\Result;
use App\Models\Exam;
use Illuminate\Http\Request;

class ResultController extends Controller
{
    public function index(Request $request)
    {
        $user  = $request->user();
        $query = Result::with(['student.user', 'exam.course']);

        if ($user->isStudent()) {
            // Students only see their own results
            $query->whereHas('student', fn($q) => $q->where('user_id', $user->id));
        }

        if ($user->isInstructor()) {
            // Instructors see results for their own exams
            $query->whereHas('exam', fn($q) => $q->where('created_by', $user->id));
        }

        $query->when($request->exam_id, fn($q, $id) => $q->where('exam_id', $id))
              ->when($request->student_id, fn($q, $id) => $q->where('student_id', $id));

        $results = $query->latest()->paginate($request->per_page ?? 20);

        return response()->json($results);
    }

    public function show(Result $result)
    {
        return response()->json(
            $result->load(['student.user', 'exam.course', 'studentExam.answers.question'])
        );
    }

    /**
     * Summary stats for admin dashboard.
     */
    public function stats(Request $request)
    {
        $examId = $request->exam_id;

        $query = Result::when($examId, fn($q) => $q->where('exam_id', $examId));

        return response()->json([
            'total'       => $query->count(),
            'passed'      => (clone $query)->where('is_passed', true)->count(),
            'failed'      => (clone $query)->where('is_passed', false)->count(),
            'avg_score'   => round($query->avg('percentage'), 2),
            'highest'     => $query->max('percentage'),
            'lowest'      => $query->min('percentage'),
        ]);
    }

    /**
     * Exam-level leaderboard.
     */
    public function examResults(Exam $exam)
    {
        $results = Result::with('student.user')
            ->where('exam_id', $exam->id)
            ->orderByDesc('obtained_marks')
            ->get();

        return response()->json($results);
    }

    /**
     * Add/update instructor comment on a result.
     */
    public function comment(Request $request, Result $result)
    {
        $request->validate(['remarks' => 'required|string|max:1000']);
        $result->update(['remarks' => $request->remarks]);
        return response()->json($result->fresh());
    }

    /**
     * Student's own exam history.
     */
    public function history(Request $request)
    {
        $user    = $request->user();
        $student = $user->student;

        if (! $student) {
            return response()->json(['message' => 'Student profile not found.'], 404);
        }

        $history = Result::where('student_id', $student->id)
            ->with(['exam.course'])
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json($history);
    }
}
