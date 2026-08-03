<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Results</h1>
        <p class="text-gray-500 text-sm mt-1">{{ auth.isStudent ? 'Your exam history' : 'All student results' }}</p>
      </div>
      <button v-if="!auth.isStudent" @click="doExport" :disabled="exporting"
        class="bg-green-600 hover:bg-green-700 disabled:opacity-60 text-white text-sm font-semibold px-4 py-2.5 rounded-lg flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
        </svg>
        {{ exporting ? 'Exporting...' : 'Export CSV' }}
      </button>
    </div>

    <!-- Stats row (admin/instructor) -->
    <div v-if="!auth.isStudent && stats" class="grid grid-cols-2 sm:grid-cols-4 gap-4">
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
        <p class="text-2xl font-bold text-gray-800">{{ stats.total||0 }}</p>
        <p class="text-xs text-gray-500 mt-1">Total</p>
      </div>
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
        <p class="text-2xl font-bold text-green-600">{{ stats.passed||0 }}</p>
        <p class="text-xs text-gray-500 mt-1">Passed</p>
      </div>
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
        <p class="text-2xl font-bold text-red-500">{{ stats.failed||0 }}</p>
        <p class="text-xs text-gray-500 mt-1">Failed</p>
      </div>
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
        <p class="text-2xl font-bold text-indigo-600">{{ stats.avg_score||0 }}%</p>
        <p class="text-xs text-gray-500 mt-1">Avg Score</p>
      </div>
    </div>

    <!-- Filter bar -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 flex flex-wrap gap-3">
      <div class="relative">
        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input v-model="search" @input="debouncedFetch" placeholder="Search exam or student…"
          class="border border-gray-200 rounded-lg pl-9 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 w-56"/>
      </div>
      <select v-model="filterPassed" @change="fetchResults()"
        class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
        <option value="">All Results</option>
        <option value="1">Passed Only</option>
        <option value="0">Failed Only</option>
      </select>
      <input v-model="filterDate" @change="fetchResults()" type="date"
        class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"/>
      <button @click="clearFilters"
        class="text-sm text-gray-500 hover:text-red-500 px-3 py-2 rounded-lg hover:bg-red-50 transition-colors">
        Clear
      </button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th v-if="!auth.isStudent" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Student</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Exam</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Score</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Percentage</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-if="loading"><td :colspan="auth.isStudent?6:7" class="py-10 text-center text-gray-400">
              <svg class="animate-spin w-6 h-6 text-indigo-500 mx-auto" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
              </svg>
            </td></tr>
            <tr v-else-if="!results.length"><td :colspan="auth.isStudent?6:7" class="py-10 text-center text-gray-400">No results found.</td></tr>
            <tr v-else v-for="r in results" :key="r.id" class="hover:bg-gray-50 transition-colors">
              <td v-if="!auth.isStudent" class="px-4 py-3 font-medium text-gray-800">{{ r.student?.user?.name||'—' }}</td>
              <td class="px-4 py-3">
                <p class="font-medium text-gray-800">{{ r.exam?.title||'—' }}</p>
                <p class="text-xs text-gray-400">{{ r.exam?.course?.title }}</p>
              </td>
              <td class="px-4 py-3 text-gray-600 font-medium">{{ r.obtained_marks }} / {{ r.total_marks }}</td>
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <div class="w-20 bg-gray-200 rounded-full h-2">
                    <div :class="r.is_passed?'bg-green-500':'bg-red-400'" class="h-2 rounded-full"
                      :style="{ width: Math.min(r.percentage,100)+'%' }"/>
                  </div>
                  <span :class="r.is_passed?'text-green-600':'text-red-600'" class="font-semibold text-sm">{{ r.percentage }}%</span>
                </div>
              </td>
              <td class="px-4 py-3">
                <span :class="r.is_passed?'bg-green-100 text-green-700':'bg-red-100 text-red-700'"
                  class="px-2.5 py-0.5 rounded-full text-xs font-semibold">
                  {{ r.is_passed?'PASS':'FAIL' }}
                </span>
              </td>
              <td class="px-4 py-3 text-gray-400 text-xs">{{ fmtDate(r.created_at) }}</td>
              <td class="px-4 py-3">
                <router-link :to="`/results/${r.id}`" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">Details</router-link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <!-- Pagination -->
      <div v-if="meta && meta.last_page > 1" class="px-4 py-3 border-t border-gray-100 flex items-center justify-between">
        <p class="text-xs text-gray-500">Showing {{ meta.from }}–{{ meta.to }} of {{ meta.total }}</p>
        <div class="flex gap-1">
          <button v-for="p in meta.last_page" :key="p" @click="fetchResults(p)"
            :class="['w-8 h-8 text-xs rounded-lg font-medium', p===meta.current_page?'bg-indigo-600 text-white':'text-gray-600 hover:bg-gray-100']">
            {{ p }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/axios'
import { downloadCsv } from '@/utils/exportCsv'

const auth         = useAuthStore()
const results      = ref([])
const stats        = ref(null)
const meta         = ref(null)
const loading      = ref(true)
const exporting    = ref(false)
const search       = ref('')
const filterPassed = ref('')
const filterDate   = ref('')

async function doExport() {
  exporting.value = true
  const params = {}
  if (filterPassed.value !== '') params.is_passed = filterPassed.value
  if (filterDate.value)           params.date      = filterDate.value
  await downloadCsv('/export/results', params, 'results.csv')
  exporting.value = false
}

let debounce
function debouncedFetch() { clearTimeout(debounce); debounce = setTimeout(() => fetchResults(), 350) }

async function fetchResults(page = 1) {
  loading.value = true
  try {
    const params = { page }
    if (filterPassed.value !== '') params.is_passed = filterPassed.value
    if (filterDate.value)           params.date      = filterDate.value
    if (search.value)               params.search    = search.value
    const { data } = await api.get('/results', { params })
    results.value  = data.data
    meta.value     = data.meta
  } finally { loading.value = false }
}

async function fetchStats() {
  if (auth.isStudent) return
  try { const { data } = await api.get('/results/stats'); stats.value = data } catch (_) {}
}

function clearFilters() {
  search.value = ''; filterPassed.value = ''; filterDate.value = ''
  fetchResults()
}

function fmtDate(d) {
  return d ? new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '—'
}

onMounted(() => { fetchResults(); fetchStats() })
</script>
