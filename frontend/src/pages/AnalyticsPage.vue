<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Analytics</h1>
        <p class="text-gray-500 text-sm mt-1">Performance statistics and reports</p>
      </div>
      <div class="flex gap-3">
        <select v-model="selectedExam" @change="loadData"
          class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
          <option value="">All Exams</option>
          <option v-for="e in examsList" :key="e.id" :value="e.id">{{ e.title }}</option>
        </select>
        <button @click="doExport" :disabled="exporting"
          class="bg-green-600 hover:bg-green-700 disabled:opacity-60 text-white text-sm font-semibold px-4 py-2 rounded-lg flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
          </svg>
          {{ exporting ? 'Exporting...' : 'Export CSV' }}
        </button>
      </div>
    </div>

    <div v-if="loading" class="grid grid-cols-2 lg:grid-cols-4 gap-5">
      <div v-for="i in 6" :key="i" class="bg-white rounded-xl h-24 animate-pulse border border-gray-100"/>
    </div>

    <template v-else>
      <!-- KPI cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 text-center">
          <p class="text-3xl font-black text-gray-800">{{ d.total || 0 }}</p>
          <p class="text-xs text-gray-500 mt-1 font-medium">Total Submissions</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 text-center">
          <p class="text-3xl font-black text-indigo-600">{{ d.avg_score || 0 }}%</p>
          <p class="text-xs text-gray-500 mt-1 font-medium">Average Score</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 text-center">
          <p class="text-3xl font-black text-green-600">{{ d.highest || 0 }}%</p>
          <p class="text-xs text-gray-500 mt-1 font-medium">Highest Score</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 text-center">
          <p class="text-3xl font-black text-red-500">{{ d.lowest || 0 }}%</p>
          <p class="text-xs text-gray-500 mt-1 font-medium">Lowest Score</p>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <!-- Pass / Fail pie (CSS-based) -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
          <h2 class="font-semibold text-gray-800 mb-4">Pass / Fail Ratio</h2>
          <div class="flex items-center gap-8">
            <!-- Donut chart using conic-gradient -->
            <div class="relative w-32 h-32 flex-shrink-0">
              <div class="w-32 h-32 rounded-full"
                :style="`background: conic-gradient(#22c55e 0% ${d.pass_rate||0}%, #ef4444 ${d.pass_rate||0}% 100%)`"/>
              <div class="absolute inset-4 bg-white rounded-full flex items-center justify-center">
                <span class="text-sm font-black text-gray-800">{{ d.pass_rate||0 }}%</span>
              </div>
            </div>
            <div class="space-y-3">
              <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-green-500 flex-shrink-0"/>
                <span class="text-sm text-gray-600">Passed: <strong class="text-green-600">{{ d.passed||0 }}</strong></span>
              </div>
              <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-red-500 flex-shrink-0"/>
                <span class="text-sm text-gray-600">Failed: <strong class="text-red-500">{{ d.failed||0 }}</strong></span>
              </div>
              <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-gray-300 flex-shrink-0"/>
                <span class="text-sm text-gray-600">Total: <strong class="text-gray-700">{{ d.total||0 }}</strong></span>
              </div>
            </div>
          </div>
        </div>

        <!-- Score distribution bar chart -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
          <h2 class="font-semibold text-gray-800 mb-4">Score Distribution</h2>
          <div class="space-y-3">
            <div v-for="bucket in d.buckets || []" :key="bucket.label">
              <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-medium text-gray-600">{{ bucket.label }}%</span>
                <span class="text-xs text-gray-500">{{ bucket.count }} students</span>
              </div>
              <div class="w-full bg-gray-100 rounded-full h-5">
                <div class="h-5 rounded-full flex items-center justify-end pr-2 transition-all duration-500"
                  :style="{ width: barWidth(bucket.count) + '%', background: barColor(bucket.label) }">
                  <span v-if="bucket.count > 0" class="text-white text-xs font-bold">{{ bucket.count }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Per-exam performance bar chart -->
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <h2 class="font-semibold text-gray-800 mb-5">Per-Exam Average Score</h2>
        <div v-if="!d.per_exam?.length" class="text-center text-gray-400 py-8">No exam data available.</div>
        <div v-else class="space-y-3">
          <div v-for="e in d.per_exam" :key="e.exam_id">
            <div class="flex items-center justify-between mb-1">
              <span class="text-xs font-medium text-gray-700 truncate max-w-xs">{{ e.exam?.title || 'Exam #'+e.exam_id }}</span>
              <span class="text-xs text-gray-500 flex-shrink-0 ml-2">{{ Number(e.avg_pct).toFixed(1) }}% avg · {{ e.submissions }} taken</span>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-6 relative overflow-hidden">
              <div class="h-6 bg-indigo-500 rounded-full flex items-center justify-end pr-3 transition-all duration-500"
                :style="{ width: Math.min(Number(e.avg_pct), 100) + '%' }">
                <span class="text-white text-xs font-semibold">{{ Number(e.avg_pct).toFixed(0) }}%</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Monthly trend -->
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <h2 class="font-semibold text-gray-800 mb-5">Monthly Submission Trend (Last 6 Months)</h2>
        <div v-if="!trend.length" class="text-center text-gray-400 py-8">No trend data.</div>
        <div v-else class="flex items-end gap-3 h-40">
          <div v-for="t in trend" :key="t.month"
            class="flex-1 flex flex-col items-center gap-1">
            <span class="text-xs text-gray-500 font-medium">{{ t.submissions }}</span>
            <div class="w-full rounded-t-lg bg-indigo-500 transition-all duration-500"
              :style="{ height: barHeightPx(t.submissions, maxTrend) + 'px' }"/>
            <span class="text-xs text-gray-400 rotate-45 origin-left whitespace-nowrap">{{ t.month }}</span>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/axios'
import { useAuthStore } from '@/stores/auth'
import { downloadCsv } from '@/utils/exportCsv'

const auth         = useAuthStore()
const d            = ref({})
const trend        = ref([])
const examsList    = ref([])
const selectedExam = ref('')
const loading      = ref(true)
const exporting    = ref(false)

async function doExport() {
  exporting.value = true
  const params = selectedExam.value ? { exam_id: selectedExam.value } : {}
  await downloadCsv('/export/results', params, 'results.csv')
  exporting.value = false
}

const maxTrend = computed(() => Math.max(...trend.value.map(t => t.submissions), 1))

async function loadData() {
  loading.value = true
  try {
    const params = selectedExam.value ? { exam_id: selectedExam.value } : {}
    const [perf, tr] = await Promise.all([
      api.get('/analytics/performance', { params }),
      api.get('/analytics/trend'),
    ])
    d.value     = perf.data
    trend.value = tr.data
  } finally { loading.value = false }
}

async function loadExams() {
  try {
    const { data } = await api.get('/exams', { params: { per_page: 100 } })
    examsList.value = data.data
  } catch (_) {}
}

function barWidth(count) {
  const max = Math.max(...(d.value.buckets || []).map(b => b.count), 1)
  return Math.max((count / max) * 100, count > 0 ? 4 : 0)
}

function barColor(label) {
  const colors = { '0-20': '#ef4444', '21-40': '#f97316', '41-60': '#eab308', '61-80': '#22c55e', '81-100': '#6366f1' }
  return colors[label] || '#6366f1'
}

function barHeightPx(val, max) { return Math.max((val / max) * 120, val > 0 ? 6 : 0) }

onMounted(() => { loadData(); loadExams() })
</script>
