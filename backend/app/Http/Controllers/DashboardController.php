<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Exam;
use App\Models\Instructor;
use App\Models\Notification;
use App\Models\Result;
use App\Models\Student;
use App\Models\StudentExam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        $user = $request->user();

        if ($user->isAdmin())      return $this->adminStats();
        if ($user->isInstructor()) return $this->instructorStats($user);
        if ($user->isStudent())    return $this->studentStats($user);

        return response()->json([]);
    }

    // ─── Admin ────────────────────────────────────────────────────────────────
    private function adminStats()
    {
        $totalResults = Result::count();

        // Last 5 exam submissions
        $recentResults = Result::with(['student.user', 'exam'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($r) => [
                'student'   => $r->student?->user?->name ?? '—',
                'exam'      => $r->exam?->title ?? '—',
                'score'     => (float) ($r->percentage ?? 0),
                'is_passed' => (bool)  $r->is_passed,
                'date'      => $r->created_at?->diffForHumans() ?? '—',
            ]);

        // Monthly submission trend — MySQL compatible
        $driver = config('database.default');
        $monthExpr = $driver === 'sqlite'
            ? DB::raw("strftime('%Y-%m', created_at) AS month")
            : DB::raw("DATE_FORMAT(created_at, '%Y-%m') AS month");

        $trend = Result::select($monthExpr, DB::raw('COUNT(*) AS submissions'))
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return response()->json([
            'total_students'    => Student::count(),
            'total_instructors' => Instructor::count(),
            'total_courses'     => Course::count(),
            'total_exams'       => Exam::count(),
            'active_exams'      => Exam::where('status', 'active')->count(),
            'total_results'     => $totalResults,
            'pass_rate'         => $totalResults
                ? round(Result::where('is_passed', 1)->count() / $totalResults * 100, 1)
                : 0,
            'recent_results'    => $recentResults,
            'trend'             => $trend,
        ]);
    }

    // ─── Instructor ───────────────────────────────────────────────────────────
    private function instructorStats($user)
    {
        $examIds    = Exam::where('created_by', $user->id)->pluck('id');
        $totalSubs  = Result::whereIn('exam_id', $examIds)->count();
        $passedSubs = Result::whereIn('exam_id', $examIds)->where('is_passed', 1)->count();

        // Last 5 results for instructor's exams
        $recentResults = Result::with(['student.user', 'exam'])
            ->whereIn('exam_id', $examIds)
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($r) => [
                'student'   => $r->student?->user?->name ?? '—',
                'exam'      => $r->exam?->title ?? '—',
                'score'     => (float) ($r->percentage ?? 0),
                'is_passed' => (bool)  $r->is_passed,
                'date'      => $r->created_at?->diffForHumans() ?? '—',
            ]);

        // Per-exam stats — avoid eager-loading on grouped queries
        $examStats = Exam::where('created_by', $user->id)
            ->withCount('questions')
            ->latest()
            ->limit(8)
            ->get()
            ->map(function ($e) {
                $results    = Result::where('exam_id', $e->id)->get();
                $count      = $results->count();
                $passedCnt  = $results->where('is_passed', 1)->count();
                return [
                    'id'          => $e->id,
                    'title'       => $e->title,
                    'status'      => $e->status,
                    'submissions' => $count,
                    'avg_score'   => $count ? round($results->avg('percentage'), 1) : 0,
                    'pass_rate'   => $count ? round($passedCnt / $count * 100, 1)  : 0,
                ];
            });

        // Top 5 students in instructor's exams (MySQL-safe)
        $topRows = Result::whereIn('exam_id', $examIds)
            ->select(
                'student_id',
                DB::raw('ROUND(AVG(percentage),1) AS avg_score'),
                DB::raw('COUNT(*) AS exams_taken')
            )
            ->groupBy('student_id')
            ->orderByDesc('avg_score')
            ->limit(5)
            ->get();

        $studentIds  = $topRows->pluck('student_id');
        $studentMap  = \App\Models\Student::with('user:id,name')
                         ->whereIn('id', $studentIds)
                         ->get()->keyBy('id');

        $topStudents = $topRows->map(fn($r) => [
            'name'        => $studentMap->get($r->student_id)?->user?->name ?? '—',
            'avg_score'   => (float) ($r->avg_score ?? 0),
            'exams_taken' => (int)   $r->exams_taken,
        ]);

        return response()->json([
            'my_exams'          => $examIds->count(),
            'active_exams'      => Exam::where('created_by', $user->id)->where('status', 'active')->count(),
            'total_courses'     => Course::whereHas('instructor', fn($q) => $q->where('user_id', $user->id))->count(),
            'total_submissions' => $totalSubs,
            'pass_rate'         => $totalSubs ? round($passedSubs / $totalSubs * 100, 1) : 0,
            'recent_results'    => $recentResults,
            'exam_stats'        => $examStats,
            'top_students'      => $topStudents,
        ]);
    }

    // ─── Student ──────────────────────────────────────────────────────────────
    private function studentStats($user)
    {
        $student = $user->student;

        if (! $student) {
            return response()->json([
                'message' => 'Student profile not found.',
            ], 404);
        }

        // Recent results
        $recentResults = Result::where('student_id', $student->id)
            ->with(['exam.course'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($r) => [
                'exam'       => $r->exam?->title ?? '—',
                'course'     => $r->exam?->course?->title ?? '—',
                'score'      => (int)   $r->obtained_marks,
                'total'      => (int)   $r->total_marks,
                'percentage' => (float) ($r->percentage ?? 0),
                'is_passed'  => (bool)  $r->is_passed,
                'date'       => $r->created_at?->format('M d, Y') ?? '—',
                'result_id'  => $r->id,
            ]);

        // Upcoming active exams not yet started by student
        $takenExamIds = StudentExam::where('student_id', $student->id)->pluck('exam_id');

        $upcoming = Exam::where('status', 'active')
            ->whereNotIn('id', $takenExamIds)
            ->where('end_date', '>', now())
            ->with('course:id,title')
            ->orderBy('start_date')
            ->limit(5)
            ->get()
            ->map(fn($e) => [
                'id'         => $e->id,
                'title'      => $e->title,
                'course'     => $e->course?->title ?? '—',
                'start_date' => $e->start_date,
                'end_date'   => $e->end_date,
                'duration'   => $e->duration,
                'is_open'    => now()->between($e->start_date, $e->end_date),
            ]);

        // Completed exam attempts
        $completed = StudentExam::where('student_id', $student->id)
            ->whereIn('status', ['submitted', 'graded'])
            ->with(['exam.course', 'result'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($se) => [
                'exam'      => $se->exam?->title ?? '—',
                'course'    => $se->exam?->course?->title ?? '—',
                'submitted' => $se->submitted_at?->format('M d, Y') ?? '—',
                'score'     => (float) ($se->result?->percentage ?? 0),
                'is_passed' => (bool)  ($se->result?->is_passed ?? false),
                'result_id' => $se->result?->id,
            ]);

        // Unread notifications
        $notifications = Notification::where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get();

        // Active courses
        $courses = Course::where('is_active', 1)
            ->with('instructor.user:id,name')
            ->limit(6)
            ->get();

        return response()->json([
            'exams_taken'     => Result::where('student_id', $student->id)->count(),
            'exams_passed'    => Result::where('student_id', $student->id)->where('is_passed', 1)->count(),
            'avg_percentage'  => round(Result::where('student_id', $student->id)->avg('percentage') ?? 0, 2),
            'available_exams' => Exam::where('status', 'active')->count(),
            'recent_results'  => $recentResults,
            'upcoming_exams'  => $upcoming,
            'completed_exams' => $completed,
            'notifications'   => $notifications,
            'courses'         => $courses,
        ]);
    }
}
