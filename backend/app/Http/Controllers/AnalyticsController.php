<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Result;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Performance analytics — global or per-exam.
     * Compatible with both MySQL and SQLite.
     */
    public function performance(Request $request)
    {
        $user  = $request->user();
        $query = Result::query();

        // Scope to instructor's own exams
        if ($user->isInstructor()) {
            $myExamIds = Exam::where('created_by', $user->id)->pluck('id');
            $query->whereIn('exam_id', $myExamIds);
        }

        // Optional single-exam filter
        if ($request->filled('exam_id')) {
            $query->where('exam_id', (int) $request->exam_id);
        }

        $total   = (clone $query)->count();
        $passed  = (clone $query)->where('is_passed', 1)->count();
        $failed  = $total - $passed;
        $avg     = round((clone $query)->avg('percentage') ?? 0, 2);
        $highest = round((clone $query)->max('percentage') ?? 0, 2);
        $lowest  = round((clone $query)->min('percentage') ?? 0, 2);

        // Score distribution buckets
        $buckets = $this->scoreBuckets(clone $query);

        // Per-exam chart data — avoid ->with() on grouped queries (breaks in MySQL)
        $perExamScope = Result::query();
        if ($user->isInstructor()) {
            $myExamIds = Exam::where('created_by', $user->id)->pluck('id');
            $perExamScope->whereIn('exam_id', $myExamIds);
        }
        if ($request->filled('exam_id')) {
            $perExamScope->where('exam_id', (int) $request->exam_id);
        }

        $perExamRaw = $perExamScope
            ->select(
                'exam_id',
                DB::raw('ROUND(AVG(percentage), 2) AS avg_pct'),
                DB::raw('COUNT(*) AS submissions'),
                DB::raw('SUM(CASE WHEN is_passed = 1 THEN 1 ELSE 0 END) AS passed_count')
            )
            ->groupBy('exam_id')
            ->orderByDesc('exam_id')
            ->limit(10)
            ->get();

        // Fetch exam titles in a separate query to avoid groupBy+eager-load bug
        $examIds = $perExamRaw->pluck('exam_id');
        $examMap = Exam::select('id', 'title')
                       ->whereIn('id', $examIds)
                       ->pluck('title', 'id');

        $perExam = $perExamRaw->map(fn($row) => [
            'exam_id'      => $row->exam_id,
            'avg_pct'      => (float) ($row->avg_pct ?? 0),
            'submissions'  => (int)   $row->submissions,
            'passed_count' => (int)   ($row->passed_count ?? 0),
            'exam'         => [
                'id'    => $row->exam_id,
                'title' => $examMap[$row->exam_id] ?? 'Exam #'.$row->exam_id,
            ],
        ]);

        return response()->json([
            'total'     => $total,
            'passed'    => $passed,
            'failed'    => $failed,
            'pass_rate' => $total > 0 ? round($passed / $total * 100, 1) : 0,
            'fail_rate' => $total > 0 ? round($failed / $total * 100, 1) : 0,
            'avg_score' => $avg,
            'highest'   => $highest,
            'lowest'    => $lowest,
            'buckets'   => $buckets,
            'per_exam'  => $perExam,
        ]);
    }

    /**
     * Top-student ranking by average score.
     * Avoids ->with() on grouped SELECT to stay MySQL-compatible.
     */
    public function ranking(Request $request)
    {
        $rows = Result::select(
                'student_id',
                DB::raw('ROUND(AVG(percentage), 2) AS avg_score'),
                DB::raw('COUNT(*) AS exams_taken'),
                DB::raw('SUM(CASE WHEN is_passed = 1 THEN 1 ELSE 0 END) AS exams_passed')
            )
            ->groupBy('student_id')
            ->orderByDesc('avg_score')
            ->limit((int) ($request->limit ?? 20))
            ->get();

        // Load student data separately
        $studentIds = $rows->pluck('student_id');
        $students   = Student::with('user:id,name,email')
                        ->whereIn('id', $studentIds)
                        ->get()
                        ->keyBy('id');

        $ranking = $rows->values()->map(function ($r, $idx) use ($students) {
            $student = $students->get($r->student_id);
            return [
                'rank'         => $idx + 1,
                'student_id'   => $r->student_id,
                'name'         => $student?->user?->name  ?? '—',
                'email'        => $student?->user?->email ?? '—',
                'avg_score'    => (float) ($r->avg_score ?? 0),
                'exams_taken'  => (int)   $r->exams_taken,
                'exams_passed' => (int)   ($r->exams_passed ?? 0),
            ];
        });

        return response()->json($ranking);
    }

    /**
     * Monthly submission trend for the last 6 months.
     * Uses DATE_FORMAT for MySQL, strftime for SQLite.
     */
    public function trend(Request $request)
    {
        $user   = $request->user();
        $driver = config('database.default');

        // Date format differs between MySQL and SQLite
        $monthExpr = $driver === 'sqlite'
            ? DB::raw("strftime('%Y-%m', created_at) AS month")
            : DB::raw("DATE_FORMAT(created_at, '%Y-%m') AS month");

        $query = Result::select(
                $monthExpr,
                DB::raw('COUNT(*) AS submissions'),
                DB::raw('SUM(CASE WHEN is_passed = 1 THEN 1 ELSE 0 END) AS passed')
            )
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month');

        if ($user->isInstructor()) {
            $myExamIds = Exam::where('created_by', $user->id)->pluck('id');
            $query->whereIn('exam_id', $myExamIds);
        }

        return response()->json($query->get());
    }

    /**
     * Build score distribution buckets from a Result query.
     */
    private function scoreBuckets($query): array
    {
        $buckets = [
            '0-20'   => 0,
            '21-40'  => 0,
            '41-60'  => 0,
            '61-80'  => 0,
            '81-100' => 0,
        ];

        $query->select('percentage')->get()->each(function ($r) use (&$buckets) {
            $p = (float) $r->percentage;
            if      ($p <= 20) $buckets['0-20']++;
            elseif  ($p <= 40) $buckets['21-40']++;
            elseif  ($p <= 60) $buckets['41-60']++;
            elseif  ($p <= 80) $buckets['61-80']++;
            else               $buckets['81-100']++;
        });

        return array_map(
            fn($label, $count) => ['label' => $label, 'count' => $count],
            array_keys($buckets),
            $buckets
        );
    }
}
