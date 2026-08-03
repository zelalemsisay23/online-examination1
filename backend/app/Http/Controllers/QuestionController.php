<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Question;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function index(Exam $exam)
    {
        return response()->json($exam->questions()->orderBy('order')->get());
    }

    public function store(Request $request, Exam $exam)
    {
        $request->validate([
            'question_text'  => 'required|string',
            'question_type'  => 'required|in:mcq,true_false,short_answer',
            'options'        => 'nullable|array',
            'options.*'      => 'string',
            'correct_answer' => 'required|string',
            'marks'          => 'required|integer|min:1',
            'order'          => 'integer|min:0',
        ]);

        // For true_false, enforce options
        if ($request->question_type === 'true_false') {
            $request->merge(['options' => ['True', 'False']]);
        }

        $question = $exam->questions()->create($request->only([
            'question_text', 'question_type', 'options', 'correct_answer', 'marks', 'order',
        ]));

        // Recalculate exam total marks
        $exam->recalculateTotalMarks();

        return response()->json($question, 201);
    }

    public function show(Exam $exam, Question $question)
    {
        return response()->json($question);
    }

    public function update(Request $request, Exam $exam, Question $question)
    {
        $request->validate([
            'question_text'  => 'sometimes|string',
            'question_type'  => 'sometimes|in:mcq,true_false,short_answer',
            'options'        => 'nullable|array',
            'options.*'      => 'string',
            'correct_answer' => 'sometimes|string',
            'marks'          => 'sometimes|integer|min:1',
            'order'          => 'sometimes|integer|min:0',
        ]);

        if ($request->question_type === 'true_false') {
            $request->merge(['options' => ['True', 'False']]);
        }

        $question->update($request->only([
            'question_text', 'question_type', 'options', 'correct_answer', 'marks', 'order',
        ]));

        $exam->recalculateTotalMarks();

        return response()->json($question->fresh());
    }

    public function destroy(Exam $exam, Question $question)
    {
        $question->delete();
        $exam->recalculateTotalMarks();

        return response()->json(['message' => 'Question deleted successfully.']);
    }

    /**
     * Bulk store questions for an exam.
     */
    public function bulkStore(Request $request, Exam $exam)
    {
        $request->validate([
            'questions'                  => 'required|array|min:1',
            'questions.*.question_text'  => 'required|string',
            'questions.*.question_type'  => 'required|in:mcq,true_false,short_answer',
            'questions.*.options'        => 'nullable|array',
            'questions.*.correct_answer' => 'required|string',
            'questions.*.marks'          => 'required|integer|min:1',
        ]);

        $created = [];
        foreach ($request->questions as $index => $q) {
            if (($q['question_type'] ?? '') === 'true_false') {
                $q['options'] = ['True', 'False'];
            }
            $q['order'] = $index;
            $created[] = $exam->questions()->create($q);
        }

        $exam->recalculateTotalMarks();

        return response()->json($created, 201);
    }
}
