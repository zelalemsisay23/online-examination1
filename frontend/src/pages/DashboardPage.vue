<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
        <p class="text-gray-500 text-sm mt-1">Welcome back, <strong>{{ auth.user?.name }}</strong></p>
      </div>
      <span :class="roleBadge.cls" class="px-3 py-1 rounded-full text-xs font-bold capitalize">
        {{ roleBadge.label }}
      </span>
    </div>

    <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
      <div v-for="i in 4" :key="i" class="bg-white rounded-xl h-28 animate-pulse border border-gray-100"/>
    </div>

    <!-- ═══════════ ADMIN ═══════════ -->
    <template v-else-if="auth.isAdmin">
      <!-- Stat cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <StatCard label="Students"    :value="s.total_students    || 0" color-class="bg-blue-100 text-blue-600">
          <template #icon><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg></template>
        </StatCard>
        <StatCard label="Instructors" :value="s.total_instructors || 0" color-class="bg-purple-100 text-purple-600">
          <template #icon><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/></svg></template>
        </StatCard>
        <StatCard label="Total Exams" :value="s.total_exams       || 0" color-class="bg-indigo-100 text-indigo-600" :sub="`${s.active_exams||0} active`">
          <template #icon><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg></template>
        </StatCard>
        <StatCard label="Pass Rate"   :value="(s.pass_rate||0)+'%'" color-class="bg-green-100 text-green-600" :sub="`${s.total_results||0} submissions`">
          <template #icon><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></template>
        </StatCard>
      </div>

      <!-- Row 1: Students list + Instructors list -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <!-- Students -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
          <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-800">👨‍🎓 Students</h2>
            <router-link to="/students" class="text-xs text-indigo-600 hover:underline">Manage all →</router-link>
          </div>
          <div v-if="loadingLists" class="px-5 py-6 text-center text-gray-400 text-sm">Loading...</div>
          <div v-else-if="!students.length" class="px-5 py-6 text-center text-gray-400 text-sm">No students yet.</div>
          <div v-else class="divide-y divide-gray-50">
            <div v-for="st in students" :key="st.id" class="px-5 py-3 flex items-center gap-3">
              <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-700 font-bold text-xs flex items-center justify-center flex-shrink-0">
                {{ initials(st.user?.name) }}
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-800 truncate">{{ st.user?.name }}</p>
                <p class="text-xs text-gray-400 truncate">{{ st.user?.email }}</p>
              </div>
              <span :class="st.user?.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'"
                class="text-xs px-2 py-0.5 rounded-full font-semibold flex-shrink-0">
                {{ st.user?.is_active ? 'Active' : 'Inactive' }}
              </span>
            </div>
          </div>
        </div>

        <!-- Instructors -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
          <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-800">🎓 Instructors</h2>
            <router-link to="/instructors" class="text-xs text-indigo-600 hover:underline">Manage all →</router-link>
          </div>
          <div v-if="loadingLists" class="px-5 py-6 text-center text-gray-400 text-sm">Loading...</div>
          <div v-else-if="!instructors.length" class="px-5 py-6 text-center text-gray-400 text-sm">No instructors yet.</div>
          <div v-else class="divide-y divide-gray-50">
            <div v-for="ins in instructors" :key="ins.id" class="px-5 py-3 flex items-center gap-3">
              <div class="w-8 h-8 rounded-full bg-purple-100 text-purple-700 font-bold text-xs flex items-center justify-center flex-shrink-0">
                {{ initials(ins.user?.name) }}
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-800 truncate">{{ ins.user?.name }}</p>
                <p class="text-xs text-gray-400 truncate">{{ ins.department || ins.user?.email }}</p>
              </div>
              <span :class="ins.user?.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'"
                class="text-xs px-2 py-0.5 rounded-full font-semibold flex-shrink-0">
                {{ ins.user?.is_active ? 'Active' : 'Inactive' }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Row 2: Courses + Exams -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <!-- Courses -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
          <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-800">📚 Courses</h2>
            <router-link to="/courses" class="text-xs text-indigo-600 hover:underline">Manage all →</router-link>
          </div>
          <div v-if="loadingLists" class="px-5 py-6 text-center text-gray-400 text-sm">Loading...</div>
          <div v-else-if="!courses.length" class="px-5 py-6 text-center text-gray-400 text-sm">No courses yet.</div>
          <div v-else class="divide-y divide-gray-50">
            <div v-for="c in courses" :key="c.id" class="px-5 py-3 flex items-center gap-3">
              <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 font-bold text-xs flex items-center justify-center flex-shrink-0">
                {{ c.code?.slice(0,2) }}
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-800 truncate">{{ c.title }}</p>
                <p class="text-xs text-gray-400">{{ c.instructor?.user?.name || 'No instructor' }}</p>
              </div>
              <span :class="c.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                class="text-xs px-2 py-0.5 rounded-full font-semibold flex-shrink-0">
                {{ c.is_active ? 'Active' : 'Inactive' }}
              </span>
            </div>
          </div>
        </div>

        <!-- Exams -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
          <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-800">📝 Exams</h2>
            <router-link to="/exams" class="text-xs text-indigo-600 hover:underline">Manage all →</router-link>
          </div>
          <div v-if="loadingLists" class="px-5 py-6 text-center text-gray-400 text-sm">Loading...</div>
          <div v-else-if="!exams.length" class="px-5 py-6 text-center text-gray-400 text-sm">No exams yet.</div>
          <div v-else class="divide-y divide-gray-50">
            <div v-for="e in exams" :key="e.id" class="px-5 py-3 flex items-center gap-3">
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-800 truncate">{{ e.title }}</p>
                <p class="text-xs text-gray-400">{{ e.course?.title }}</p>
              </div>
              <span :class="{
                  'bg-green-100 text-green-700': e.status === 'active',
                  'bg-gray-100 text-gray-600':   e.status === 'draft',
                  'bg-blue-100 text-blue-700':   e.status === 'completed',
                }" class="text-xs px-2 py-0.5 rounded-full font-semibold flex-shrink-0 capitalize">
                {{ e.status }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Row 3: Recent submissions + Quick actions -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-100 shadow-sm">
          <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-800">Recent Submissions</h2>
            <router-link to="/results" class="text-xs text-indigo-600 hover:underline">View all →</router-link>
          </div>
          <div class="divide-y divide-gray-50">
            <div v-if="!s.recent_results?.length" class="px-5 py-6 text-center text-gray-400 text-sm">No submissions yet.</div>
            <div v-for="r in s.recent_results" :key="r.exam+r.student"
              class="px-5 py-3 flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-gray-800">{{ r.student }}</p>
                <p class="text-xs text-gray-500">{{ r.exam }}</p>
              </div>
              <div class="flex items-center gap-3">
                <span class="text-sm font-bold" :class="r.is_passed?'text-green-600':'text-red-500'">{{ r.score }}%</span>
                <span :class="r.is_passed?'bg-green-100 text-green-700':'bg-red-100 text-red-700'"
                  class="text-xs px-2 py-0.5 rounded-full font-semibold">
                  {{ r.is_passed ? 'PASS' : 'FAIL' }}
                </span>
                <span class="text-xs text-gray-400">{{ r.date }}</span>
              </div>
            </div>
          </div>
        </div>
        <div class="space-y-3">
          <h2 class="font-semibold text-gray-800 px-1">Quick Actions</h2>
          <QuickAction to="/students"     label="Manage Students"    desc="Add, edit, remove"  icon="users"     color="blue"   />
          <QuickAction to="/instructors"  label="Manage Instructors" desc="Add, edit, remove"  icon="academic"  color="purple" />
          <QuickAction to="/exams"        label="Manage Exams"       desc="Create and publish" icon="clipboard" color="indigo" />
          <QuickAction to="/analytics"    label="Analytics"          desc="Charts & reports"   icon="chart"     color="green"  />
          <QuickAction to="/activity-log" label="System Logs"        desc="Monitor activity"   icon="log"       color="yellow" />
        </div>
      </div>
    </template>

    <!-- ═══════════ INSTRUCTOR ═══════════ -->
    <template v-else-if="auth.isInstructor">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <StatCard label="My Exams"          :value="s.my_exams||0"          color-class="bg-indigo-100 text-indigo-600">
          <template #icon><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg></template>
        </StatCard>
        <StatCard label="Active Exams"      :value="s.active_exams||0"      color-class="bg-green-100 text-green-600">
          <template #icon><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg></template>
        </StatCard>
        <StatCard label="Submissions"       :value="s.total_submissions||0" color-class="bg-blue-100 text-blue-600">
          <template #icon><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7"/></svg></template>
        </StatCard>
        <StatCard label="Pass Rate"         :value="(s.pass_rate||0)+'%'"   color-class="bg-purple-100 text-purple-600">
          <template #icon><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></template>
        </StatCard>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <!-- Exam performance table -->
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-100 shadow-sm">
          <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-800">Exam Performance</h2>
            <router-link to="/analytics" class="text-xs text-indigo-600 hover:underline">Full analytics →</router-link>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-gray-50"><tr>
                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Exam</th>
                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Submissions</th>
                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Avg Score</th>
                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Pass Rate</th>
              </tr></thead>
              <tbody class="divide-y divide-gray-50">
                <tr v-if="!s.exam_stats?.length"><td colspan="5" class="px-4 py-6 text-center text-gray-400">No data yet.</td></tr>
                <tr v-for="e in s.exam_stats" :key="e.id" class="hover:bg-gray-50">
                  <td class="px-4 py-2.5 font-medium text-gray-800 max-w-xs truncate">{{ e.title }}</td>
                  <td class="px-4 py-2.5"><span :class="e.status==='active'?'bg-green-100 text-green-700':e.status==='draft'?'bg-gray-100 text-gray-600':'bg-blue-100 text-blue-700'" class="text-xs px-2 py-0.5 rounded-full font-semibold capitalize">{{ e.status }}</span></td>
                  <td class="px-4 py-2.5 text-gray-600">{{ e.submissions }}</td>
                  <td class="px-4 py-2.5 text-gray-600">{{ e.avg_score }}%</td>
                  <td class="px-4 py-2.5"><span :class="e.pass_rate>=50?'text-green-600':'text-red-500'" class="font-semibold">{{ e.pass_rate }}%</span></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <!-- Top students + quick actions -->
        <div class="space-y-4">
          <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <h2 class="font-semibold text-gray-800 mb-3">Top Students</h2>
            <div class="space-y-2">
              <div v-if="!s.top_students?.length" class="text-center text-gray-400 text-sm py-4">No data yet.</div>
              <div v-for="(st, i) in s.top_students" :key="st.name" class="flex items-center gap-3">
                <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold flex items-center justify-center">{{ i+1 }}</span>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-800 truncate">{{ st.name }}</p>
                  <p class="text-xs text-gray-500">{{ st.exams_taken }} exams taken</p>
                </div>
                <span class="text-sm font-bold text-indigo-600">{{ st.avg_score }}%</span>
              </div>
            </div>
          </div>
          <QuickAction to="/exams"     label="My Exams"    desc="Manage exams"        icon="clipboard" color="indigo"/>
          <QuickAction to="/analytics" label="Analytics"   desc="View full charts"    icon="chart"     color="green"/>
          <QuickAction to="/results"   label="Results"     desc="Student results"     icon="chart"     color="blue"/>
        </div>
      </div>
    </template>

    <!-- ═══════════ STUDENT ═══════════ -->
    <template v-else>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <StatCard label="Exams Taken"     :value="s.exams_taken    ||0" color-class="bg-indigo-100 text-indigo-600">
          <template #icon><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/></svg></template>
        </StatCard>
        <StatCard label="Exams Passed"    :value="s.exams_passed   ||0" color-class="bg-green-100 text-green-600">
          <template #icon><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></template>
        </StatCard>
        <StatCard label="Avg Score"       :value="(s.avg_percentage||0)+'%'" color-class="bg-blue-100 text-blue-600">
          <template #icon><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2z"/></svg></template>
        </StatCard>
        <StatCard label="Available"       :value="s.available_exams||0" color-class="bg-yellow-100 text-yellow-600">
          <template #icon><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></template>
        </StatCard>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <!-- Upcoming exams -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
          <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-800">📅 Upcoming Exams</h2>
            <router-link to="/exams" class="text-xs text-indigo-600 hover:underline">All →</router-link>
          </div>
          <div class="divide-y divide-gray-50">
            <div v-if="!s.upcoming_exams?.length" class="px-5 py-6 text-center text-gray-400 text-sm">No upcoming exams.</div>
            <div v-for="e in s.upcoming_exams" :key="e.id" class="px-5 py-3">
              <div class="flex items-start justify-between gap-2">
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-semibold text-gray-800 truncate">{{ e.title }}</p>
                  <p class="text-xs text-indigo-600">{{ e.course }}</p>
                  <p class="text-xs text-gray-400 mt-0.5">⏱ {{ e.duration }} min · 📅 {{ fmtDate(e.start_date) }}</p>
                </div>
                <router-link v-if="e.is_open" :to="`/take-exam/${e.id}`"
                  class="flex-shrink-0 text-xs bg-indigo-600 hover:bg-indigo-700 text-white px-2.5 py-1.5 rounded-lg font-semibold">
                  Start
                </router-link>
                <span v-else class="flex-shrink-0 text-xs bg-yellow-100 text-yellow-700 px-2.5 py-1.5 rounded-lg font-semibold">
                  Soon
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent results -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
          <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-800">📊 Recent Results</h2>
            <router-link to="/results" class="text-xs text-indigo-600 hover:underline">All →</router-link>
          </div>
          <div class="divide-y divide-gray-50">
            <div v-if="!s.recent_results?.length" class="px-5 py-6 text-center text-gray-400 text-sm">No results yet.</div>
            <div v-for="r in s.recent_results" :key="r.result_id" class="px-5 py-3 flex items-center justify-between">
              <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-gray-800 truncate">{{ r.exam }}</p>
                <p class="text-xs text-gray-400">{{ r.date }}</p>
              </div>
              <div class="flex items-center gap-2 flex-shrink-0 ml-2">
                <span class="text-sm font-bold" :class="r.is_passed?'text-green-600':'text-red-500'">{{ r.percentage }}%</span>
                <router-link :to="`/results/${r.result_id}`" class="text-xs text-indigo-600 hover:underline">View</router-link>
              </div>
            </div>
          </div>
        </div>

        <!-- Notifications + Courses -->
        <div class="space-y-4">
          <!-- Notifications -->
          <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
              <h2 class="font-semibold text-gray-800">🔔 Notifications</h2>
              <router-link to="/notifications" class="text-xs text-indigo-600 hover:underline">All →</router-link>
            </div>
            <div class="divide-y divide-gray-50">
              <div v-if="!s.notifications?.length" class="px-5 py-4 text-center text-gray-400 text-sm">No notifications.</div>
              <div v-for="n in s.notifications" :key="n.id" class="px-5 py-3">
                <p class="text-sm font-semibold text-gray-800">{{ n.title }}</p>
                <p class="text-xs text-gray-500 truncate">{{ n.message }}</p>
              </div>
            </div>
          </div>
          <!-- Courses -->
          <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
            <div class="px-5 py-4 border-b border-gray-100">
              <h2 class="font-semibold text-gray-800">📚 Courses</h2>
            </div>
            <div class="px-5 py-3 space-y-2">
              <div v-if="!s.courses?.length" class="text-center text-gray-400 text-sm py-2">No courses.</div>
              <div v-for="c in s.courses" :key="c.id" class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-700 font-bold text-xs flex items-center justify-center flex-shrink-0">{{ c.code?.slice(0,2) }}</span>
                <div class="min-w-0">
                  <p class="text-sm font-medium text-gray-800 truncate">{{ c.title }}</p>
                  <p class="text-xs text-gray-500">{{ c.instructor?.user?.name || 'Unassigned' }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/axios'
import StatCard    from '@/components/StatCard.vue'
import QuickAction from '@/components/QuickAction.vue'

const auth         = useAuthStore()
const s            = ref({})
const loading      = ref(true)
const loadingLists = ref(false)
const students     = ref([])
const instructors  = ref([])
const courses      = ref([])
const exams        = ref([])

const roleBadge = computed(() => ({
  admin:      { label: 'Administrator', cls: 'bg-indigo-100 text-indigo-700' },
  instructor: { label: 'Instructor',    cls: 'bg-purple-100 text-purple-700' },
  student:    { label: 'Student',       cls: 'bg-green-100 text-green-700' },
}[auth.userRole] || { label: auth.userRole, cls: 'bg-gray-100 text-gray-600' }))

function initials(name) {
  return (name || 'U').split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase()
}

function fmtDate(d) {
  return d ? new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '—'
}

async function loadAdminLists() {
  loadingLists.value = true
  try {
    const [st, ins, cs, ex] = await Promise.all([
      api.get('/students',    { params: { per_page: 8 } }),
      api.get('/instructors', { params: { per_page: 8 } }),
      api.get('/courses',     { params: { per_page: 8 } }),
      api.get('/exams',       { params: { per_page: 8 } }),
    ])
    students.value    = st.data.data    || []
    instructors.value = ins.data.data   || []
    courses.value     = cs.data.data    || []
    exams.value       = ex.data.data    || []
  } catch (e) {
    console.error('Dashboard admin lists error:', e)
  } finally {
    loadingLists.value = false
  }
}

onMounted(async () => {
  try {
    const { data } = await api.get('/dashboard/stats')
    s.value = data
  } finally {
    loading.value = false
  }
  // Load admin lists after stats (auth.isAdmin is always known at this point
  // because the user is already authenticated before reaching this page)
  if (auth.isAdmin) {
    loadAdminLists()
  }
})
</script>
