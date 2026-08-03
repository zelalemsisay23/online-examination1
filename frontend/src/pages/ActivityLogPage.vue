<template>
  <div class="space-y-5">
    <div>
      <h1 class="text-2xl font-bold text-gray-800">System Logs</h1>
      <p class="text-gray-500 text-sm mt-1">Monitor all important system activities</p>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 flex flex-wrap gap-3">
      <div class="relative">
        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none"
          stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input v-model="filterAction" @input="debouncedFetch" placeholder="Search action…"
          class="border border-gray-200 rounded-lg pl-9 pr-4 py-2 text-sm
                 focus:outline-none focus:ring-2 focus:ring-indigo-400 w-52"/>
      </div>
      <input v-model="filterDate" @change="fetchLogs()" type="date"
        class="border border-gray-200 rounded-lg px-3 py-2 text-sm
               focus:outline-none focus:ring-2 focus:ring-indigo-400"/>
      <button @click="clearFilters"
        class="text-sm text-gray-500 hover:text-red-500 px-3 py-2 rounded-lg hover:bg-red-50">
        Clear
      </button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">#</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">User</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Action</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Detail</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">IP</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Time</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-if="loading">
              <td colspan="6" class="py-10 text-center text-gray-400">
                <svg class="animate-spin w-6 h-6 text-indigo-500 mx-auto" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                </svg>
              </td>
            </tr>
            <tr v-else-if="!logs.length">
              <td colspan="6" class="py-10 text-center text-gray-400">No activity logs found.</td>
            </tr>
            <tr v-else v-for="log in logs" :key="log.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 text-gray-400 text-xs">{{ log.id }}</td>
              <td class="px-4 py-3">
                <p class="font-medium text-gray-800 text-xs">{{ log.user?.name || 'System' }}</p>
                <p class="text-gray-400 text-xs">{{ log.user?.email }}</p>
              </td>
              <td class="px-4 py-3">
                <span :class="actionBadge(log.action)" class="text-xs px-2 py-0.5 rounded-full font-semibold">
                  {{ log.action }}
                </span>
              </td>
              <td class="px-4 py-3 text-gray-500 text-xs max-w-xs truncate">
                {{ log.detail || '—' }}
              </td>
              <td class="px-4 py-3 text-gray-400 text-xs font-mono">{{ log.ip_address || '—' }}</td>
              <td class="px-4 py-3 text-gray-400 text-xs whitespace-nowrap">{{ fmtDate(log.created_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="meta && meta.last_page > 1"
        class="px-4 py-3 border-t border-gray-100 flex items-center justify-between">
        <p class="text-xs text-gray-500">Showing {{ meta.from }}–{{ meta.to }} of {{ meta.total }}</p>
        <div class="flex gap-1">
          <button v-for="p in Math.min(meta.last_page, 10)" :key="p" @click="fetchLogs(p)"
            :class="['w-8 h-8 text-xs rounded-lg font-medium',
              p===meta.current_page ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100']">
            {{ p }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/axios'

const logs         = ref([])
const meta         = ref(null)
const loading      = ref(true)
const filterAction = ref('')
const filterDate   = ref('')

let debounce
function debouncedFetch() {
  clearTimeout(debounce)
  debounce = setTimeout(() => fetchLogs(), 350)
}

async function fetchLogs(page = 1) {
  loading.value = true
  try {
    const params = { page, per_page: 30 }
    if (filterAction.value) params.action = filterAction.value
    if (filterDate.value)   params.date   = filterDate.value
    const { data } = await api.get('/activity-log', { params })
    logs.value = data.data
    meta.value = data.meta
  } finally { loading.value = false }
}

function clearFilters() {
  filterAction.value = ''
  filterDate.value   = ''
  fetchLogs()
}

function actionBadge(action) {
  if (!action) return 'bg-gray-100 text-gray-600'
  const a = action.toLowerCase()
  if (a.includes('violation') || a.includes('exit') || a.includes('block') || a.includes('blur'))
    return 'bg-red-100 text-red-700'
  if (a.includes('submit') || a.includes('login'))
    return 'bg-green-100 text-green-700'
  if (a.includes('start') || a.includes('create'))
    return 'bg-blue-100 text-blue-700'
  if (a.includes('webcam') || a.includes('fullscreen'))
    return 'bg-yellow-100 text-yellow-700'
  return 'bg-gray-100 text-gray-600'
}

function fmtDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleString('en-US', {
    month: 'short', day: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}

onMounted(fetchLogs)
</script>
