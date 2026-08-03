# Online Examination System — Frontend

Vue.js 3 SPA. Communicates with the Laravel backend in `../backend`.

---

## Requirements
- Node.js 18+
- npm

---

## Setup

```bash
cd exam/frontend

# 1. Install dependencies
npm install

# 2. Copy environment file
cp .env.example .env

# 3. Edit .env if backend runs on a different port/host
#    VITE_API_URL=http://localhost:8000/api

# 4. Start development server (port 5173)
npm run dev
```

App is now available at **http://localhost:5173**

To build for production:
```bash
npm run build      # outputs to dist/
npm run preview    # preview the production build locally
```

---

## Environment Variables

| Variable       | Default                        | Description              |
|----------------|--------------------------------|--------------------------|
| VITE_API_URL   | http://localhost:8000/api      | Laravel backend API URL  |

---

## Project Structure

```
src/
  main.js                   Entry point
  App.vue                   Root component
  axios.js                  Axios instance (reads VITE_API_URL, attaches Bearer token)
  assets/
    app.css                 Tailwind CSS + shared utility classes
  router/
    index.js                Vue Router with role-based guards
  stores/
    auth.js                 Pinia auth store (login, logout, changePassword)
  layouts/
    AppLayout.vue           Sidebar + Navbar shell
  components/
    AppNavbar.vue           Top navigation bar with user menu
    AppSidebar.vue          Collapsible sidebar with role-filtered links
    SidebarLink.vue         Individual sidebar link with active state
    BaseModal.vue           Reusable modal (teleport + fade transition)
    StatCard.vue            Dashboard stat card
    QuickAction.vue         Dashboard shortcut card
    ExamTimer.vue           Countdown timer with auto-expire event
    QuestionCard.vue        MCQ / True-False / Short Answer renderer
  pages/
    LoginPage.vue           Login form with demo credential buttons
    DashboardPage.vue       Role-aware stats and quick actions
    StudentsPage.vue        Student CRUD (admin only)
    InstructorsPage.vue     Instructor CRUD (admin only)
    CoursesPage.vue         Course CRUD (admin/instructor)
    ExamsPage.vue           Exam list with create/delete (admin/instructor) + take (student)
    ExamDetailPage.vue      Manage questions for an exam
    TakeExamPage.vue        Full exam flow: intro → take → submit → result
    ResultsPage.vue         Results list with pass/fail filter
    ResultDetailPage.vue    Per-question answer review
    ProfilePage.vue         View profile + change password
    NotFoundPage.vue        404 page
```

---

## Running Both Projects Together

Open two terminals:

```bash
# Terminal 1 — Backend
cd exam/backend
php artisan serve          # http://localhost:8000

# Terminal 2 — Frontend
cd exam/frontend
npm run dev                # http://localhost:5173
```

Then open **http://localhost:5173** in your browser.
