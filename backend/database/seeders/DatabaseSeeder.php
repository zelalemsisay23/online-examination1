<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Exam;
use App\Models\Instructor;
use App\Models\Question;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Admin ──────────────────────────────────────────────────────────────
        User::create([
            'name'     => 'System Admin',
            'email'    => 'admin@exam.com',
            'password' => Hash::make('password'),
            'role'     => 'admin',
        ]);

        // ── Instructors ────────────────────────────────────────────────────────
        $instructorUsers = [
            ['name' => 'Dr. Alice Johnson',  'email' => 'alice@exam.com',  'dept' => 'Computer Science', 'spec' => 'Web Development'],
            ['name' => 'Prof. Bob Williams', 'email' => 'bob@exam.com',    'dept' => 'Mathematics',      'spec' => 'Calculus'],
        ];

        $instructors = [];
        foreach ($instructorUsers as $i => $data) {
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => Hash::make('password'),
                'role'     => 'instructor',
            ]);
            $instructors[] = Instructor::create([
                'user_id'        => $user->id,
                'employee_id'    => 'EMP' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'department'     => $data['dept'],
                'specialization' => $data['spec'],
            ]);
        }

        // ── Students ───────────────────────────────────────────────────────────
        $studentData = [
            ['name' => 'Charlie Brown',  'email' => 'charlie@exam.com'],
            ['name' => 'Diana Prince',   'email' => 'diana@exam.com'],
            ['name' => 'Eve Thompson',   'email' => 'eve@exam.com'],
        ];

        $students = [];
        foreach ($studentData as $i => $data) {
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => Hash::make('password'),
                'role'     => 'student',
            ]);
            $students[] = Student::create([
                'user_id'    => $user->id,
                'student_id' => 'STU' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
            ]);
        }

        // ── Courses ────────────────────────────────────────────────────────────
        $courses = [
            Course::create([
                'title'         => 'Introduction to Web Development',
                'code'          => 'CS101',
                'description'   => 'Fundamentals of HTML, CSS, and JavaScript.',
                'instructor_id' => $instructors[0]->id,
            ]),
            Course::create([
                'title'         => 'Calculus I',
                'code'          => 'MATH101',
                'description'   => 'Limits, derivatives, and integrals.',
                'instructor_id' => $instructors[1]->id,
            ]),
        ];

        // ── Exams ──────────────────────────────────────────────────────────────
        $adminUser = User::where('email', 'admin@exam.com')->first();

        $exam = Exam::create([
            'title'         => 'Web Development Midterm',
            'description'   => 'Covers HTML, CSS, and basic JavaScript.',
            'course_id'     => $courses[0]->id,
            'created_by'    => $instructors[0]->user_id,
            'start_date'    => now()->subHour(),
            'end_date'      => now()->addDays(7),
            'duration'      => 60,
            'total_marks'   => 0,
            'passing_marks' => 6,
            'status'        => 'active',
        ]);

        // ── Questions ──────────────────────────────────────────────────────────
        $questions = [
            [
                'question_type'  => 'mcq',
                'question_text'  => 'Which tag is used to define a hyperlink in HTML?',
                'options'        => ['<link>', '<a>', '<href>', '<url>'],
                'correct_answer' => '<a>',
                'marks'          => 2,
                'order'          => 1,
            ],
            [
                'question_type'  => 'mcq',
                'question_text'  => 'Which CSS property controls the text size?',
                'options'        => ['font-style', 'text-size', 'font-size', 'text-font'],
                'correct_answer' => 'font-size',
                'marks'          => 2,
                'order'          => 2,
            ],
            [
                'question_type'  => 'true_false',
                'question_text'  => 'JavaScript is a server-side programming language.',
                'options'        => ['True', 'False'],
                'correct_answer' => 'False',
                'marks'          => 2,
                'order'          => 3,
            ],
            [
                'question_type'  => 'mcq',
                'question_text'  => 'What does CSS stand for?',
                'options'        => ['Computer Style Sheets', 'Creative Style Sheets', 'Cascading Style Sheets', 'Colorful Style Sheets'],
                'correct_answer' => 'Cascading Style Sheets',
                'marks'          => 2,
                'order'          => 4,
            ],
            [
                'question_type'  => 'true_false',
                'question_text'  => 'The <div> element is a block-level element.',
                'options'        => ['True', 'False'],
                'correct_answer' => 'True',
                'marks'          => 2,
                'order'          => 5,
            ],
        ];

        foreach ($questions as $q) {
            Question::create(array_merge($q, ['exam_id' => $exam->id]));
        }

        $exam->recalculateTotalMarks();
    }
}
