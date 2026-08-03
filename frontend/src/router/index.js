import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

import AppLayout from '@/layouts/AppLayout.vue'

import LoginPage           from '@/pages/LoginPage.vue'
import DashboardPage       from '@/pages/DashboardPage.vue'
import StudentsPage        from '@/pages/StudentsPage.vue'
import InstructorsPage     from '@/pages/InstructorsPage.vue'
import CoursesPage         from '@/pages/CoursesPage.vue'
import ExamsPage           from '@/pages/ExamsPage.vue'
import ExamDetailPage      from '@/pages/ExamDetailPage.vue'
import TakeExamPage        from '@/pages/TakeExamPage.vue'
import ResultsPage         from '@/pages/ResultsPage.vue'
import ResultDetailPage    from '@/pages/ResultDetailPage.vue'
import ProfilePage         from '@/pages/ProfilePage.vue'
import AnalyticsPage       from '@/pages/AnalyticsPage.vue'
import RankingPage         from '@/pages/RankingPage.vue'
import ActivityLogPage     from '@/pages/ActivityLogPage.vue'
import NotificationsPage   from '@/pages/NotificationsPage.vue'
import NotFoundPage        from '@/pages/NotFoundPage.vue'

const routes = [
  { path: '/login', name: 'login', component: LoginPage, meta: { guest: true } },

  {
    path: '/',
    component: AppLayout,
    meta: { requiresAuth: true },
    children: [
      { path: '',                redirect: '/dashboard' },
      { path: 'dashboard',       name: 'dashboard',        component: DashboardPage },
      { path: 'students',        name: 'students',         component: StudentsPage,        meta: { roles: ['admin'] } },
      { path: 'instructors',     name: 'instructors',      component: InstructorsPage,     meta: { roles: ['admin'] } },
      { path: 'courses',         name: 'courses',          component: CoursesPage,         meta: { roles: ['admin', 'instructor'] } },
      { path: 'exams',           name: 'exams',            component: ExamsPage },
      { path: 'exams/:id',       name: 'exam-detail',      component: ExamDetailPage,      meta: { roles: ['admin', 'instructor'] } },
      { path: 'take-exam/:id',   name: 'take-exam',        component: TakeExamPage,        meta: { roles: ['student'] } },
      { path: 'results',         name: 'results',          component: ResultsPage },
      { path: 'results/:id',     name: 'result-detail',    component: ResultDetailPage },
      { path: 'analytics',       name: 'analytics',        component: AnalyticsPage,       meta: { roles: ['admin', 'instructor'] } },
      { path: 'ranking',         name: 'ranking',          component: RankingPage,         meta: { roles: ['admin', 'instructor'] } },
      { path: 'activity-log',    name: 'activity-log',     component: ActivityLogPage,     meta: { roles: ['admin'] } },
      { path: 'notifications',   name: 'notifications',    component: NotificationsPage },
      { path: 'profile',         name: 'profile',          component: ProfilePage },
    ],
  },

  { path: '/:pathMatch(.*)*', name: 'not-found', component: NotFoundPage },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: () => ({ top: 0 }),
})

router.beforeEach((to, _from, next) => {
  const auth = useAuthStore()
  if (to.meta.requiresAuth && !auth.isLoggedIn)               return next('/login')
  if (to.meta.guest        && auth.isLoggedIn)                return next('/dashboard')
  if (to.meta.roles        && !to.meta.roles.includes(auth.userRole)) return next('/dashboard')
  next()
})

export default router
