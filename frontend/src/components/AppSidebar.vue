<template>
  <!-- Mobile overlay -->
  <div v-if="open" class="fixed inset-0 bg-black/40 z-20 lg:hidden" @click="$emit('close')"/>

  <aside :class="[
    'bg-indigo-900 text-white flex flex-col transition-all duration-300 z-30 flex-shrink-0',
    'fixed lg:static inset-y-0 left-0 h-full',
    open ? 'w-64' : 'w-0 lg:w-16 overflow-hidden',
  ]">
    <!-- Logo -->
    <div class="flex items-center gap-3 px-4 py-5 border-b border-indigo-800 flex-shrink-0">
      <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center flex-shrink-0">
        <svg class="w-5 h-5 text-indigo-700" fill="currentColor" viewBox="0 0 20 20">
          <path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969
            7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255
            0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1
            1 0 11-2 0V4.804z"/>
        </svg>
      </div>
      <span v-show="open" class="font-bold text-sm whitespace-nowrap leading-tight">
        ExamSystem
      </span>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto py-4 space-y-0.5 px-2">
      <template v-for="link in visibleLinks" :key="link.to">
        <!-- Section divider -->
        <div v-if="link.divider && open" class="px-3 pt-4 pb-1">
          <p class="text-indigo-500 text-xs font-semibold uppercase tracking-wider">{{ link.divider }}</p>
        </div>
        <SidebarLink v-else
          :to="link.to" :icon="link.icon" :label="link.label" :collapsed="!open"
          :badge="link.badge"/>
      </template>
    </nav>

    <!-- Footer -->
    <div v-show="open" class="px-4 py-3 border-t border-indigo-800 text-xs text-indigo-400">
      Online Exam System v2.0
    </div>
  </aside>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import SidebarLink from '@/components/SidebarLink.vue'
import api from '@/axios'

defineProps({ open: Boolean })
defineEmits(['close'])

const auth   = useAuthStore()
const unread = ref(0)

const allLinks = [
  // Main
  { to: '/dashboard',    label: 'Dashboard',    icon: 'home',      roles: ['admin','instructor','student'] },
  // Management
  { divider: 'Management', roles: ['admin','instructor'] },
  { to: '/students',     label: 'Students',     icon: 'users',     roles: ['admin'] },
  { to: '/instructors',  label: 'Instructors',  icon: 'academic',  roles: ['admin'] },
  { to: '/courses',      label: 'Courses',      icon: 'book',      roles: ['admin','instructor'] },
  // Exams
  { divider: 'Examinations', roles: ['admin','instructor','student'] },
  { to: '/exams',        label: 'Exams',        icon: 'clipboard', roles: ['admin','instructor','student'] },
  { to: '/results',      label: 'Results',      icon: 'chart',     roles: ['admin','instructor','student'] },
  { to: '/ranking',      label: 'Ranking',      icon: 'ranking',   roles: ['admin','instructor'] },
  { to: '/analytics',    label: 'Analytics',    icon: 'analytics', roles: ['admin','instructor'] },
  // System
  { divider: 'System', roles: ['admin'] },
  { to: '/activity-log', label: 'System Logs',  icon: 'log',       roles: ['admin'] },
  // Personal
  { divider: 'Personal', roles: ['admin','instructor','student'] },
  { to: '/notifications', label: 'Notifications', icon: 'bell',    roles: ['admin','instructor','student'], badge: unread },
  { to: '/profile',      label: 'Profile',      icon: 'user',      roles: ['admin','instructor','student'] },
]

const visibleLinks = computed(() =>
  allLinks.filter(l => l.roles?.includes(auth.userRole))
)

onMounted(async () => {
  try {
    const { data } = await api.get('/notifications/unread-count')
    unread.value = data.count || 0
  } catch (_) {}
})
</script>
