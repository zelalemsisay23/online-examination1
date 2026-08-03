<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Notifications</h1>
        <p class="text-gray-500 text-sm mt-1">
          <span v-if="unread > 0" class="text-indigo-600 font-semibold">{{ unread }} unread</span>
          <span v-else>All caught up!</span>
        </p>
      </div>
      <div class="flex gap-2">
        <button @click="markAllRead"
          class="text-sm border border-indigo-200 text-indigo-600 hover:bg-indigo-50
                 px-4 py-2 rounded-lg font-medium transition-colors">
          Mark all read
        </button>
        <!-- Broadcast (admin/instructor only) -->
        <button v-if="auth.isAdmin || auth.isInstructor"
          @click="showBroadcast = true"
          class="text-sm bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-semibold flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436
                 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988
                 3.988 0 01-1.564-.317z"/>
          </svg>
          Broadcast
        </button>
      </div>
    </div>

    <!-- Notification list -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
      <div v-if="loading" class="py-12 text-center">
        <svg class="animate-spin w-7 h-7 text-indigo-500 mx-auto" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
        </svg>
      </div>

      <div v-else-if="!notifications.length" class="py-16 text-center text-gray-400">
        <svg class="w-12 h-12 mx-auto mb-3 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2
               2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595
               1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        <p class="font-medium">No notifications yet.</p>
      </div>

      <div v-else class="divide-y divide-gray-50">
        <div v-for="n in notifications" :key="n.id"
          :class="['px-5 py-4 flex items-start gap-4 transition-colors cursor-pointer',
            n.is_read ? 'bg-white hover:bg-gray-50' : 'bg-indigo-50/50 hover:bg-indigo-50']"
          @click="markRead(n)">

          <!-- Type icon -->
          <div :class="typeIcon(n.type).bg"
            class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 text-lg">
            {{ typeIcon(n.type).emoji }}
          </div>

          <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between gap-2">
              <p class="font-semibold text-gray-800 text-sm">{{ n.title }}</p>
              <div class="flex items-center gap-2 flex-shrink-0">
                <span v-if="!n.is_read"
                  class="w-2 h-2 rounded-full bg-indigo-500 flex-shrink-0"/>
                <span class="text-xs text-gray-400 whitespace-nowrap">{{ fmtDate(n.created_at) }}</span>
              </div>
            </div>
            <p class="text-sm text-gray-500 mt-0.5">{{ n.message }}</p>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="meta && meta.last_page > 1"
        class="px-4 py-3 border-t border-gray-100 flex items-center justify-between">
        <p class="text-xs text-gray-500">{{ meta.total }} notifications</p>
        <div class="flex gap-1">
          <button v-for="p in Math.min(meta.last_page, 8)" :key="p" @click="fetchNotifications(p)"
            :class="['w-8 h-8 text-xs rounded-lg font-medium',
              p===meta.current_page ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100']">
            {{ p }}
          </button>
        </div>
      </div>
    </div>

    <!-- Broadcast modal (admin/instructor) -->
    <BaseModal v-model="showBroadcast" title="Broadcast Notification" size="md">
      <div class="space-y-4">
        <div>
          <label class="form-label">Title *</label>
          <input v-model="bForm.title" class="input-field" placeholder="New exam published" />
        </div>
        <div>
          <label class="form-label">Message *</label>
          <textarea v-model="bForm.message" rows="3" class="input-field resize-none"
            placeholder="Describe what students need to know…"/>
        </div>
        <div>
          <label class="form-label">Type</label>
          <select v-model="bForm.type" class="input-field">
            <option value="info">📘 Info</option>
            <option value="exam">📝 Exam</option>
            <option value="result">📊 Result</option>
            <option value="success">✅ Success</option>
            <option value="warning">⚠ Warning</option>
          </select>
        </div>
        <p v-if="bError" class="text-red-600 text-xs">{{ bError }}</p>
      </div>
      <template #footer>
        <button @click="showBroadcast = false" class="px-4 py-2 text-sm text-gray-600">Cancel</button>
        <button @click="sendBroadcast" :disabled="bSending"
          class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60
                 text-white text-sm font-semibold rounded-lg">
          {{ bSending ? 'Sending…' : 'Send to All Students' }}
        </button>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/axios'
import BaseModal from '@/components/BaseModal.vue'

const auth          = useAuthStore()
const notifications = ref([])
const meta          = ref(null)
const loading       = ref(true)
const unread        = ref(0)
const showBroadcast = ref(false)
const bSending      = ref(false)
const bError        = ref('')
const bForm         = ref({ title: '', message: '', type: 'info' })

async function fetchNotifications(page = 1) {
  loading.value = true
  try {
    const { data } = await api.get('/notifications', { params: { page } })
    notifications.value = data.data
    meta.value          = data.meta
    unread.value = notifications.value.filter(n => !n.is_read).length
  } finally { loading.value = false }
}

async function markRead(n) {
  if (n.is_read) return
  n.is_read = true
  unread.value = Math.max(0, unread.value - 1)
  await api.post(`/notifications/${n.id}/read`).catch(() => {})
}

async function markAllRead() {
  notifications.value.forEach(n => { n.is_read = true })
  unread.value = 0
  await api.post('/notifications/mark-all-read').catch(() => {})
}

async function sendBroadcast() {
  if (!bForm.value.title || !bForm.value.message) {
    bError.value = 'Title and message are required.'; return
  }
  bSending.value = true; bError.value = ''
  try {
    await api.post('/notifications/broadcast', bForm.value)
    showBroadcast.value = false
    bForm.value = { title: '', message: '', type: 'info' }
  } catch (e) {
    bError.value = e.response?.data?.message || 'Failed to send.'
  } finally { bSending.value = false }
}

function typeIcon(type) {
  return {
    exam:    { emoji: '📝', bg: 'bg-indigo-100' },
    result:  { emoji: '📊', bg: 'bg-blue-100' },
    success: { emoji: '✅', bg: 'bg-green-100' },
    warning: { emoji: '⚠️', bg: 'bg-yellow-100' },
    info:    { emoji: '📘', bg: 'bg-gray-100' },
  }[type] || { emoji: '🔔', bg: 'bg-gray-100' }
}

function fmtDate(d) {
  if (!d) return ''
  const date = new Date(d)
  const diff = Date.now() - date.getTime()
  if (diff < 60000)    return 'Just now'
  if (diff < 3600000)  return Math.floor(diff/60000) + 'm ago'
  if (diff < 86400000) return Math.floor(diff/3600000) + 'h ago'
  return date.toLocaleDateString('en-US', { month:'short', day:'numeric' })
}

onMounted(fetchNotifications)
</script>
