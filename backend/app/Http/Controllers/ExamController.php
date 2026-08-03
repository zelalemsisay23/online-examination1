<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\StudentExam;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Exam::with(['course', 'creator'])
            ->withCount('questions');

        // Students only see active exams
        if ($user->isStudent()) {
            $query->where('status', 'active');
        }

        // Instructors see only their own exams
        if ($user->isInstructor()) {
            $query->where('created_by', $user->id);
        }

        $query->when($request->status, fn($q, $s) => $q->where('status', $s))
              ->when($request->course_id, fn($q, $id) => $q->where('course_id', $id))
              ->when($request->search, fn($q, $s) => $q->where('title', 'like', "%$s%"));

        $exams = $query->latest()->paginate($request->per_page ?? 15);

        return response()->json($exams);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string',
            'course_id'        => 'required|exists:courses,id',
            'start_date'       => 'required|date',
            'end_date'         => 'required|date|after:start_date',
            'duration'         => 'required|integer|min:1',
            'passing_marks'    => 'required|integer|min:0',
            'status'           => 'in:draft,active,completed',
            'shuffle_questions'=> 'boolean',
        ]);

        $exam = Exam::create(array_merge(
            $request->only(['title', 'description', 'course_id', 'start_date', 'end_date',
                            'duration', 'passing_marks', 'status', 'shuffle_questions']),
            ['created_by' => $request->user()->id, 'total_marks' => 0]
        ));

        return response()->json($exam->load(['course', 'creator']), 201);
    }

    public function show(Request $request, Exam $exam)
    {
        $user = $request->user();

        $exam->load(['course', 'creator', 'questions']);

        // Students don't see correct answers
        if ($user->isStudent()) {
            $exam->questions->makeHidden(['correct_answer']);
        }

        return response()->json($exam);
    }

    public function update(Request $request, Exam $exam)
    {
        $request->validate([
            'title'            => 'sometimes|string|max:255',
            'description'      => 'nullable|string',
            'course_id'        => 'sometimes|exists:courses,id',
            'start_date'       => 'sometimes|date',
            'end_date'         => 'sometimes|date',
            'duration'         => 'sometimes|integer|min:1',
            'passing_marks'    => 'sometimes|integer|min:0',
            'status'           => 'sometimes|in:draft,active,completed',
            'shuffle_questions'=> 'sometimes|boolean',
        ]);

        $exam->update($request->only(['title', 'description', 'course_id', 'start_date',
                                      'end_date', 'duration', 'passing_marks', 'status', 'shuffle_questions']));

        return response()->json($exam->fresh()->load(['course', 'creator']));
    }

    public function destroy(Exam $exam)
    {
        $exam->delete();

        return response()->json(['message' => 'Exam deleted successfully.']);
    }
}
