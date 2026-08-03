# Online Examination System — Backend

Laravel 12 REST API. Consumed by the Vue.js frontend in `../frontend`.

---

## Requirements
- PHP 8.2+
- Composer
- SQLite (default) or MySQL

---

## Setup

```bash
cd exam/backend

# 1. Install PHP dependencies
composer install

# 2. Copy environment file
cp .env.example .env

# 3. Generate application key
php artisan key:generate

# 4. Run migrations and seed demo data
php artisan migrate:fresh --seed

# 5. Start the API server (port 8000)
php artisan serve
```

API is now available at **http://localhost:8000/api**

---

## Demo Accounts

| Role       | Email               | Password |
|------------|---------------------|----------|
| Admin      | admin@exam.com      | password |
| Instructor | alice@exam.com      | password |
| Student    | charlie@exam.com    | password |

---

## CORS

The backend allows requests from `http://localhost:5173` (Vite dev server) by default.  
To change this, update `FRONTEND_URL` in `.env`.

---

## Key Files

```
app/Http/Controllers/   — AuthController, StudentController, InstructorController,
                          CourseController, ExamController, QuestionController,
                          ExamAttemptController, ResultController, DashboardController
app/Models/             — User, Student, Instructor, Course, Exam, Question,
                          StudentExam, StudentAnswer, Result
routes/api.php          — All API endpoints
config/cors.php         — CORS configuration
database/seeders/       — Demo data seeder
```

---

## API Endpoints

```
POST   /api/login
POST   /api/logout
GET    /api/me
POST   /api/change-password
GET    /api/dashboard/stats

GET|POST              /api/students
GET|PUT|DELETE        /api/students/{id}

GET|POST              /api/instructors
GET|PUT|DELETE        /api/instructors/{id}

GET|POST              /api/courses
GET|PUT|DELETE        /api/courses/{id}

GET|POST              /api/exams
GET|PUT|DELETE        /api/exams/{id}
POST                  /api/exams/start
POST                  /api/exams/submit

GET|POST              /api/exams/{exam}/questions
POST                  /api/exams/{exam}/questions/bulk
GET|PUT|DELETE        /api/exams/{exam}/questions/{id}

GET                   /api/results
GET                   /api/results/stats
GET                   /api/results/{id}
GET                   /api/exams/{exam}/results
```
