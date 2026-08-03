<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Result;
use App\Models\Student;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    /**
     * Export results to CSV — authenticated via Bearer token (Authorization header).
     */
    public function results(Request $request)
    {
        $user  = $request->user();
        $query = Result::with(['student.user', 'exam.course']);

        if ($user->isStudent()) {
            $query->whereHas('student', fn($q) => $q->where('user_id', $user->id));
        }
        if ($user->isInstructor()) {
            $query->whereHas('exam', fn($q) => $q->where('created_by', $user->id));
        }
        if ($request->exam_id) {
            $query->where('exam_id', $request->exam_id);
        }

        $results = $query->latest()->get();

        $filename = 'results_' . now()->format('Y-m-d') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
            'Access-Control-Expose-Headers' => 'Content-Disposition',
        ];

        $callback = function () use ($results) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM so Excel opens it correctly
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'Student Name', 'Email', 'Exam Title', 'Course',
                'Score', 'Total Marks', 'Percentage', 'Status', 'Date',
            ]);
            foreach ($results as $r) {
                fputcsv($handle, [
                    $r->student?->user?->name        ?? '—',
                    $r->student?->user?->email       ?? '—',
                    $r->exam?->title                 ?? '—',
                    $r->exam?->course?->title        ?? '—',
                    $r->obtained_marks,
                    $r->total_marks,
                    $r->percentage . '%',
                    $r->is_passed ? 'PASS' : 'FAIL',
                    $r->created_at?->format('Y-m-d H:i'),
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export student list to CSV.
     */
    public function students(Request $request)
    {
        $students = Student::with('user')
            ->when($request->search, fn($q, $s) =>
                $q->whereHas('user', fn($u) => $u->where('name', 'like', "%$s%"))
            )
            ->get();

        $filename = 'students_' . now()->format('Y-m-d') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'no-cache',
            'Access-Control-Expose-Headers' => 'Content-Disposition',
        ];

        $callback = function () use ($students) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Name', 'Email', 'Student ID', 'Phone', 'Gender', 'Registered']);
            foreach ($students as $s) {
                fputcsv($handle, [
                    $s->user?->name   ?? '—',
                    $s->user?->email  ?? '—',
                    $s->student_id    ?? '—',
                    $s->phone         ?? '—',
                    $s->gender        ?? '—',
                    $s->created_at?->format('Y-m-d'),
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
