<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExamAttemptController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\InstructorController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

// ─── Public ───────────────────────────────────────────────────────────────────
Route::post('/login', [AuthController::class, 'login']);

// ─── Authenticated ────────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout',          [AuthController::class, 'logout']);
    Route::get('/me',               [AuthController::class, 'me']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    // Students
    Route::apiResource('students', StudentController::class);

    // Instructors
    Route::apiResource('instructors', InstructorController::class);

    // Courses
    Route::apiResource('courses', CourseController::class);

    // Exams
    Route::apiResource('exams', ExamController::class);

    // Questions (nested under exams)
    Route::prefix('exams/{exam}/questions')->group(function () {
        Route::get('/',              [QuestionController::class, 'index']);
        Route::post('/',             [QuestionController::class, 'store']);
        Route::post('/bulk',         [QuestionController::class, 'bulkStore']);
        Route::get('/{question}',    [QuestionController::class, 'show']);
        Route::put('/{question}',    [QuestionController::class, 'update']);
        Route::delete('/{question}', [QuestionController::class, 'destroy']);
    });

    // Exam attempts
    Route::post('/exams/start',                             [ExamAttemptController::class, 'start']);
    Route::post('/exams/submit',                            [ExamAttemptController::class, 'submit']);
    Route::post('/exams/auto-save',                         [ExamAttemptController::class, 'autoSave']);
    Route::get('/exams/load-saved/{studentExamId}',         [ExamAttemptController::class, 'loadSaved']);

    // Exam results
    Route::get('/exams/{exam}/results', [ResultController::class, 'examResults']);

    // Results
    Route::get('/results',                              [ResultController::class, 'index']);
    Route::get('/results/stats',                        [ResultController::class, 'stats']);
    Route::get('/results/history',                      [ResultController::class, 'history']);
    Route::get('/results/{result}',                     [ResultController::class, 'show']);
    Route::post('/results/{result}/comment',            [ResultController::class, 'comment']);

    // Analytics
    Route::get('/analytics/performance', [AnalyticsController::class, 'performance']);
    Route::get('/analytics/ranking',     [AnalyticsController::class, 'ranking']);
    Route::get('/analytics/trend',       [AnalyticsController::class, 'trend']);

    // Notifications
    Route::get('/notifications',              [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead']);
    Route::post('/notifications/{id}/read',   [NotificationController::class, 'markRead']);
    Route::post('/notifications/broadcast',   [NotificationController::class, 'broadcast']);

    // Activity log
    Route::get('/activity-log',  [ActivityLogController::class, 'index']);
    Route::post('/activity-log', [ActivityLogController::class, 'store']);

    // Exports
    Route::get('/export/results',  [ExportController::class, 'results']);
    Route::get('/export/students', [ExportController::class, 'students']);
});
