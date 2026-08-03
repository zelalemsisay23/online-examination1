<template>
  <div @contextmenu.prevent>

    <!-- ── Loading ─────────────────────────────────────────────────────── -->
    <div v-if="phase==='loading'" class="flex items-center justify-center h-64">
      <div class="text-center">
        <svg class="animate-spin w-10 h-10 text-indigo-500 mx-auto mb-3" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
        </svg>
        <p class="text-gray-500">Loading exam…</p>
      </div>
    </div>

    <!-- ── Error ───────────────────────────────────────────────────────── -->
    <div v-else-if="phase==='error'"
      class="bg-red-50 border border-red-200 rounded-xl p-8 text-center max-w-md mx-auto mt-12">
      <p class="text-red-700 font-semibold mb-4">{{ errorMsg }}</p>
      <router-link to="/exams" class="text-indigo-600 text-sm hover:underline">← Back to Exams</router-link>
    </div>

    <!-- ── Scheduled countdown (exam not yet open) ─────────────────────── -->
    <div v-else-if="phase==='countdown'" class="max-w-lg mx-auto mt-16">
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center">
        <div class="w-16 h-16 bg-yellow-100 text-yellow-600 rounded-2xl flex items-center justify-center mx-auto mb-5">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <h1 class="text-xl font-bold text-gray-800 mb-1">{{ examInfo.title }}</h1>
        <p class="text-gray-500 text-sm mb-6">This exam is not yet open. It starts in:</p>
        <div class="grid grid-cols-4 gap-3 mb-6">
          <div v-for="unit in countdownUnits" :key="unit.label"
            class="bg-indigo-50 rounded-xl p-3">
            <p class="text-3xl font-black text-indigo-700">{{ unit.value }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ unit.label }}</p>
          </div>
        </div>
        <p class="text-xs text-gray-400">
          Opens: {{ fmtDateTime(examInfo.start_date) }} &nbsp;|&nbsp;
          Closes: {{ fmtDateTime(examInfo.end_date) }}
        </p>
        <router-link to="/exams" class="mt-6 inline-block text-sm text-indigo-600 hover:underline">
          ← Back to Exams
        </router-link>
      </div>
    </div>

    <!-- ── Intro + Instructions + I Agree ─────────────────────────────── -->
    <div v-else-if="phase==='intro'" class="max-w-2xl mx-auto">
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        <!-- Header -->
        <div class="text-center mb-6">
          <div class="w-14 h-14 bg-indigo-100 text-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-3">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2
                   M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
          </div>
          <h1 class="text-2xl font-bold text-gray-800 mb-1">{{ examInfo.title }}</h1>
          <p class="text-indigo-600 font-medium text-sm">{{ examInfo.course?.title }}</p>
        </div>

        <!-- Stats grid -->
        <div class="grid grid-cols-4 gap-3 mb-6">
          <div class="bg-gray-50 rounded-xl p-3 text-center">
            <p class="text-xl font-bold text-gray-800">{{ examInfo.duration }}</p>
            <p class="text-xs text-gray-500">Min</p>
          </div>
          <div class="bg-gray-50 rounded-xl p-3 text-center">
            <p class="text-xl font-bold text-gray-800">{{ examInfo.questions?.length || 0 }}</p>
            <p class="text-xs text-gray-500">Questions</p>
          </div>
          <div class="bg-gray-50 rounded-xl p-3 text-center">
            <p class="text-xl font-bold text-gray-800">{{ examInfo.total_marks }}</p>
            <p class="text-xs text-gray-500">Marks</p>
          </div>
          <div class="bg-gray-50 rounded-xl p-3 text-center">
            <p class="text-xl font-bold text-gray-800">{{ examInfo.passing_marks }}</p>
            <p class="text-xs text-gray-500">Pass</p>
          </div>
        </div>

        <!-- Exam instructions -->
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-5">
          <p class="text-blue-800 font-bold text-sm mb-2">📋 Examination Instructions</p>
          <ul class="text-blue-700 text-xs space-y-1.5 list-disc list-inside">
            <li>Read each question carefully before answering.</li>
            <li>You can navigate between questions using the panel on the right.</li>
            <li>Your answers are <strong>auto-saved</strong> every 30 seconds.</li>
            <li>The exam <strong>cannot be paused</strong> once started.</li>
            <li>The exam will <strong>automatically submit</strong> when the timer expires.</li>
            <li>Do <strong>not refresh</strong> the browser while taking the exam.</li>
            <li>Switching browser tabs or exiting full-screen will be <strong>logged</strong>.</li>
            <li>Copy, paste, and right-click are <strong>disabled</strong> during the exam.</li>
          </ul>
        </div>

        <!-- Anti-cheat notice -->
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-5">
          <p class="text-red-700 font-bold text-sm mb-1">⚠ Anti-Cheating Policy</p>
          <p class="text-red-600 text-xs">
            Keyboard shortcuts (Ctrl+C/V, F12, etc.) are blocked. Suspicious activity is
            recorded and reviewed by instructors.
          </p>
        </div>

        <!-- Webcam opt-in -->
        <div v-if="webcamSupported" class="bg-gray-50 border border-gray-200 rounded-xl p-4 mb-5 flex items-start gap-3">
          <svg class="w-5 h-5 text-gray-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M15 10l4.553-2.069A1 1 0 0121 8.82v6.36a1 1 0 01-1.447.894L15 14M5 18h8a2
                 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
          </svg>
          <div>
            <p class="text-gray-700 font-semibold text-sm">Webcam Monitoring (Optional)</p>
            <label class="flex items-center gap-2 mt-1 cursor-pointer">
              <input type="checkbox" v-model="enableWebcam" class="accent-indigo-600"/>
              <span class="text-xs text-gray-600">Enable webcam for added integrity verification</span>
            </label>
            <div v-if="enableWebcam && webcamActive" class="mt-2">
              <video ref="webcamPreview" autoplay muted playsinline
                class="w-32 h-24 rounded-lg border border-blue-300 object-cover bg-black"/>
            </div>
          </div>
        </div>

        <!-- I Agree checkbox -->
        <label class="flex items-start gap-3 mb-6 cursor-pointer">
          <input type="checkbox" v-model="agreed" class="accent-indigo-600 mt-0.5 flex-shrink-0"/>
          <span class="text-sm text-gray-700">
            I have read and understood all instructions. I agree to the exam rules and
            understand that any violation may be reported to my instructor.
          </span>
        </label>

        <div class="flex gap-3">
          <router-link to="/exams"
            class="flex-1 text-center border border-gray-200 text-gray-600 hover:bg-gray-50 py-3 rounded-xl font-medium text-sm">
            Cancel
          </router-link>
          <button @click="startExam" :disabled="starting || !agreed"
            class="flex-1 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed text-white py-3 rounded-xl font-semibold text-sm">
            {{ starting ? 'Starting…' : '🔒 I Agree — Start Exam' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ── Taking exam ─────────────────────────────────────────────────── -->
    <div v-else-if="phase==='taking'"
      @copy.prevent @cut.prevent @paste.prevent @keydown="handleKeyDown">

      <!-- Warning banner -->
      <div v-if="warningMsg"
        class="fixed top-0 left-0 right-0 z-50 bg-red-600 text-white text-center py-2 px-4 text-sm font-semibold animate-pulse">
        ⚠ {{ warningMsg }}
      </div>

      <!-- Violation badge -->
      <div v-if="violations > 0"
        class="fixed top-12 right-4 z-50 bg-red-600 text-white text-xs font-bold px-3 py-1.5 rounded-full shadow-lg">
        {{ violations }} violation{{ violations !== 1 ? 's' : '' }}
      </div>

      <!-- Webcam thumbnail -->
      <div v-if="enableWebcam && webcamActive"
        class="fixed bottom-4 right-4 z-50 rounded-xl overflow-hidden border-2 border-indigo-400 shadow-lg">
        <video ref="webcamMonitor" autoplay muted playsinline class="w-32 h-24 object-cover bg-black"/>
      </div>

      <!-- Top bar -->
      <div class="sticky top-0 z-10 bg-white border-b border-gray-200 px-4 py-3 shadow-sm flex items-center gap-4 mb-4">
        <div class="flex-1 min-w-0">
          <h2 class="font-bold text-gray-800 text-sm truncate">{{ examInfo.title }}</h2>
          <p class="text-xs text-gray-500">Q {{ currentIdx + 1 }} / {{ questions.length }}</p>
        </div>
        <!-- Progress summary pills -->
        <div class="hidden md:flex items-center gap-2 text-xs">
          <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full font-semibold">
            ✓ {{ answeredCount }} answered
          </span>
          <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded-full font-semibold">
            {{ questions.length - answeredCount }} left
          </span>
          <span v-if="lastSaved" class="text-gray-400">💾 {{ lastSaved }}</span>
        </div>
        <ExamTimer :seconds="remainingTime" @expired="submitExam"/>
        <button @click="showConfirm = true"
          class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-lg flex-shrink-0">
          Submit
        </button>
      </div>

      <!-- Progress bar -->
      <div class="w-full bg-gray-200 rounded-full h-1.5 mb-4 px-4">
        <div class="bg-indigo-600 h-1.5 rounded-full transition-all"
          :style="{ width: (questions.length ? answeredCount / questions.length * 100 : 0) + '%' }"/>
      </div>

      <!-- Body: question + nav panel -->
      <div class="flex gap-4 max-w-6xl mx-auto px-4 pb-10">
        <!-- Single current question -->
        <div class="flex-1 min-w-0">
          <QuestionCard
            v-if="questions[currentIdx]"
            :question="questions[currentIdx]"
            :number="currentIdx + 1"
            v-model="answers[questions[currentIdx].id]"
          />
          <!-- Prev / Next -->
          <div class="flex justify-between mt-5">
            <button @click="goTo(currentIdx - 1)" :disabled="currentIdx === 0"
              class="px-5 py-2.5 border border-gray-200 text-gray-600 hover:bg-gray-50 disabled:opacity-40 rounded-xl text-sm font-medium">
              ← Previous
            </button>
            <button v-if="currentIdx < questions.length - 1"
              @click="goTo(currentIdx + 1)"
              class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold">
              Next →
            </button>
            <button v-else @click="showConfirm = true"
              class="px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-xl text-sm font-semibold">
              Finish & Submit
            </button>
          </div>
        </div>

        <!-- Right: question nav grid -->
        <div class="w-52 flex-shrink-0 hidden lg:block">
          <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 sticky top-24">
            <p class="text-xs font-semibold text-gray-500 uppercase mb-3">Question Navigator</p>
            <div class="grid grid-cols-5 gap-1.5">
              <button v-for="(q, idx) in questions" :key="q.id"
                @click="goTo(idx)"
                :class="[
                  'w-8 h-8 rounded-lg text-xs font-bold transition-colors',
                  idx === currentIdx
                    ? 'bg-indigo-600 text-white'
                    : answers[q.id]
                      ? 'bg-green-100 text-green-700 hover:bg-green-200'
                      : 'bg-gray-100 text-gray-500 hover:bg-gray-200'
                ]">
                {{ idx + 1 }}
              </button>
            </div>
            <div class="mt-3 space-y-1.5 text-xs">
              <div class="flex items-center gap-2">
                <span class="w-4 h-4 rounded bg-green-100 inline-block"></span>
                <span class="text-gray-500">Answered</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="w-4 h-4 rounded bg-gray-100 inline-block"></span>
                <span class="text-gray-500">Not answered</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="w-4 h-4 rounded bg-indigo-600 inline-block"></span>
                <span class="text-gray-500">Current</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Result ──────────────────────────────────────────────────────── -->
    <div v-else-if="phase==='result'" class="max-w-2xl mx-auto">
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center">
        <div :class="result.is_passed ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'"
          class="w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-5">
          <svg v-if="result.is_passed" class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <svg v-else class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <h2 class="text-2xl font-bold text-gray-800 mb-1">
          {{ result.is_passed ? 'Congratulations!' : 'Better Luck Next Time' }}
        </h2>
        <p class="text-gray-500 mb-2">{{ result.remarks }}</p>
        <p v-if="violations > 0" class="text-red-500 text-sm mb-4">
          ⚠ {{ violations }} anti-cheat violation{{ violations !== 1 ? 's' : '' }} were recorded.
        </p>
        <div class="grid grid-cols-3 gap-4 mb-8">
          <div class="bg-gray-50 rounded-xl p-4">
            <p class="text-2xl font-bold text-gray-800">{{ result.obtained_marks }}</p>
            <p class="text-xs text-gray-500 mt-1">Score</p>
          </div>
          <div class="bg-gray-50 rounded-xl p-4">
            <p :class="result.is_passed ? 'text-green-600' : 'text-red-600'" class="text-2xl font-bold">
              {{ result.percentage }}%
            </p>
            <p class="text-xs text-gray-500 mt-1">Percentage</p>
          </div>
          <div class="bg-gray-50 rounded-xl p-4">
            <p :class="result.is_passed ? 'text-green-600' : 'text-red-600'" class="text-2xl font-bold">
              {{ result.is_passed ? 'PASS' : 'FAIL' }}
            </p>
            <p class="text-xs text-gray-500 mt-1">Result</p>
          </div>
        </div>
        <div class="flex gap-3 justify-center">
          <router-link to="/exams"
            class="px-6 py-2.5 border border-gray-200 text-gray-600 rounded-xl text-sm hover:bg-gray-50">
            Back to Exams
          </router-link>
          <router-link :to="`/results/${result.id}`"
            class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-semibold text-sm">
            View Detailed Result
          </router-link>
        </div>
      </div>
    </div>

    <!-- ── Modals ──────────────────────────────────────────────────────── -->
    <BaseModal v-model="showConfirm" title="Submit Exam" size="sm">
      <p class="text-gray-600 text-sm mb-2">
        Answered: <strong class="text-green-600">{{ answeredCount }}</strong> /
        <strong>{{ questions.length }}</strong>
      </p>
      <p v-if="questions.length - answeredCount > 0" class="text-orange-600 text-xs">
        ⚠ {{ questions.length - answeredCount }} question(s) unanswered. You can still go back.
      </p>
      <template #footer>
        <button @click="showConfirm = false" class="px-4 py-2 text-sm text-gray-600">Keep Going</button>
        <button @click="submitExam" :disabled="submitting"
          class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white text-sm font-semibold rounded-lg">
          {{ submitting ? 'Submitting…' : 'Submit Now' }}
        </button>
      </template>
    </BaseModal>

    <BaseModal v-model="showForceSubmit" title="⚠ Exam Terminated" size="sm">
      <p class="text-red-700 font-semibold text-sm mb-2">Too many violations detected.</p>
      <p class="text-gray-600 text-sm">Your exam will be auto-submitted now.</p>
      <template #footer>
        <button @click="submitExam" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg w-full">
          OK — Submit Now
        </button>
      </template>
    </BaseModal>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/axios'
import ExamTimer    from '@/components/ExamTimer.vue'
import QuestionCard from '@/components/QuestionCard.vue'
import BaseModal    from '@/components/BaseModal.vue'

const route = useRoute()

// ── Core state ────────────────────────────────────────────────────────────────
const phase          = ref('loading')
const errorMsg       = ref('')
const examInfo       = ref({})
const questions      = ref([])
const answers        = ref({})
const remainingTime  = ref(0)
const studentExamId  = ref(null)
const currentIdx     = ref(0)
const starting       = ref(false)
const submitting     = ref(false)
const showConfirm    = ref(false)
const showForceSubmit = ref(false)
const result         = ref(null)
const agreed         = ref(false)
const lastSaved      = ref('')

// ── Anti-cheat state ─────────────────────────────────────────────────────────
const violations      = ref(0)
const warningMsg      = ref('')
const MAX_VIOLATIONS  = 5
const enableWebcam    = ref(false)
const webcamActive    = ref(false)
const webcamSupported = ref(!!navigator.mediaDevices?.getUserMedia)
const webcamPreview   = ref(null)
const webcamMonitor   = ref(null)
let   webcamStream    = null

// ── Countdown (scheduling) ───────────────────────────────────────────────────
const countdownUnits = ref([
  { label: 'Days',    value: '00' },
  { label: 'Hours',   value: '00' },
  { label: 'Minutes', value: '00' },
  { label: 'Seconds', value: '00' },
])
let countdownTimer = null

// ── Auto-save ────────────────────────────────────────────────────────────────
let autoSaveTimer = null
const AUTO_SAVE_INTERVAL = 30000 // 30 seconds

// ── Computed ─────────────────────────────────────────────────────────────────
const answeredCount = computed(() =>
  questions.value.filter(q => answers.value[q.id] !== undefined && answers.value[q.id] !== '').length
)

// ── Helpers ───────────────────────────────────────────────────────────────────
function fmtDateTime(d) {
  if (!d) return '—'
  return new Date(d).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function pad(n) { return String(Math.floor(n)).padStart(2, '0') }

function updateCountdown(targetDate) {
  const diff = new Date(targetDate) - Date.now()
  if (diff <= 0) {
    clearInterval(countdownTimer)
    phase.value = 'intro'
    return
  }
  const days  = diff / 86400000
  const hours = (diff % 86400000) / 3600000
  const mins  = (diff % 3600000) / 60000
  const secs  = (diff % 60000) / 1000
  countdownUnits.value = [
    { label: 'Days',    value: pad(days) },
    { label: 'Hours',   value: pad(hours) },
    { label: 'Minutes', value: pad(mins) },
    { label: 'Seconds', value: pad(secs) },
  ]
}

function goTo(idx) {
  if (idx >= 0 && idx < questions.value.length) currentIdx.value = idx
}

// ── Anti-cheat helpers ───────────────────────────────────────────────────────
function logActivity(type, detail = '') {
  api.post('/activity-log', { action: type, detail }).catch(() => {})
}

function recordViolation(type, detail) {
  violations.value++
  warningMsg.value = detail
  // Log the activity to backend so instructor can review
  logActivity(type, detail)
  // Also broadcast a cheat-alert notification to instructors via API
  api.post('/activity-log', {
    action: 'cheat_alert',
    detail: `[Exam: ${examInfo.value?.title}] ${type}: ${detail}`,
    context: { exam_id: route.params.id, violation_count: violations.value },
  }).catch(() => {})
  setTimeout(() => { warningMsg.value = '' }, 4000)
  if (violations.value >= MAX_VIOLATIONS) showForceSubmit.value = true
}

function enterFullscreen() {
  const el = document.documentElement
  if (el.requestFullscreen)            el.requestFullscreen().catch(() => {})
  else if (el.webkitRequestFullscreen) el.webkitRequestFullscreen()
  logActivity('fullscreen_enter')
}

function onFullscreenChange() {
  const inFs = !!(document.fullscreenElement || document.webkitFullscreenElement)
  if (!inFs && phase.value === 'taking') {
    recordViolation('fullscreen_exit', 'You exited full-screen mode.')
    setTimeout(enterFullscreen, 1500)
  }
}

function onVisibilityChange() {
  if (document.hidden && phase.value === 'taking')
    recordViolation('tab_switch', 'Tab switching detected. Stay on the exam page.')
}

function onWindowBlur() {
  if (phase.value === 'taking')
    recordViolation('window_blur', 'Window focus lost. Do not switch applications.')
}

function handleKeyDown(e) {
  const ctrl  = e.ctrlKey || e.metaKey
  const shift = e.shiftKey
  if (ctrl && ['c','v','x','a','p','s','u'].includes(e.key.toLowerCase())) {
    e.preventDefault(); recordViolation('key_blocked', `Ctrl+${e.key.toUpperCase()} blocked`); return
  }
  if (ctrl && shift && ['i','j','c'].includes(e.key.toLowerCase())) {
    e.preventDefault(); recordViolation('devtools', 'DevTools shortcut blocked'); return
  }
  if (['F12','F11','F5'].includes(e.key)) { e.preventDefault(); return }
  if (e.altKey && e.key === 'Tab')        { e.preventDefault(); recordViolation('alt_tab', 'Alt+Tab disabled') }
}

function attachAntiCheat() {
  document.addEventListener('visibilitychange', onVisibilityChange)
  window.addEventListener('blur', onWindowBlur)
  document.addEventListener('fullscreenchange', onFullscreenChange)
  document.addEventListener('webkitfullscreenchange', onFullscreenChange)
  logActivity('exam_started', `exam_id=${route.params.id}`)
}

function detachAntiCheat() {
  document.removeEventListener('visibilitychange', onVisibilityChange)
  window.removeEventListener('blur', onWindowBlur)
  document.removeEventListener('fullscreenchange', onFullscreenChange)
  document.removeEventListener('webkitfullscreenchange', onFullscreenChange)
  if (document.exitFullscreen && document.fullscreenElement) document.exitFullscreen().catch(() => {})
  stopWebcam()
}

// ── Webcam ────────────────────────────────────────────────────────────────────
async function startWebcam() {
  if (!enableWebcam.value || !webcamSupported.value) return
  try {
    webcamStream       = await navigator.mediaDevices.getUserMedia({ video: true, audio: false })
    webcamActive.value = true
    await nextTick()
    if (webcamPreview.value)  webcamPreview.value.srcObject  = webcamStream
    if (webcamMonitor.value)  webcamMonitor.value.srcObject  = webcamStream
  } catch (_) { webcamActive.value = false }
}

function stopWebcam() {
  webcamStream?.getTracks().forEach(t => t.stop())
  webcamStream = null; webcamActive.value = false
}

// ── Auto-save ─────────────────────────────────────────────────────────────────
async function autoSave() {
  if (!studentExamId.value || phase.value !== 'taking') return
  try {
    const payload = questions.value.map(q => ({ question_id: q.id, answer: answers.value[q.id] || null }))
    await api.post('/exams/auto-save', { student_exam_id: studentExamId.value, answers: payload })
    lastSaved.value = 'Saved ' + new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
  } catch (_) {}
}

// ── Lifecycle ─────────────────────────────────────────────────────────────────
onMounted(async () => {
  try {
    const { data } = await api.get(`/exams/${route.params.id}`)
    examInfo.value = data
    const now   = Date.now()
    const start = new Date(data.start_date).getTime()
    const end   = new Date(data.end_date).getTime()
    if (now < start) {
      phase.value = 'countdown'
      updateCountdown(data.start_date)
      countdownTimer = setInterval(() => updateCountdown(data.start_date), 1000)
    } else if (now > end) {
      errorMsg.value = 'This exam has already ended.'
      phase.value    = 'error'
    } else {
      phase.value = 'intro'
      if (enableWebcam.value) await startWebcam()
    }
  } catch (e) {
    errorMsg.value = e.response?.data?.message || 'Exam not found.'
    phase.value    = 'error'
  }
})

watch(enableWebcam, async (v) => { if (v && phase.value === 'intro') await startWebcam() })

onUnmounted(() => {
  detachAntiCheat()
  clearInterval(countdownTimer)
  clearInterval(autoSaveTimer)
})

// ── Start exam ────────────────────────────────────────────────────────────────
async function startExam() {
  starting.value = true
  errorMsg.value = ''
  try {
    await startWebcam()

    // Cast exam_id to int — route params are strings, backend validates as integer
    const { data } = await api.post('/exams/start', {
      exam_id: parseInt(route.params.id, 10),
    })

    studentExamId.value = data.student_exam.id
    questions.value     = data.questions || []
    remainingTime.value = data.remaining_time

    // Initialise all answer slots
    questions.value.forEach(q => {
      if (answers.value[q.id] === undefined) answers.value[q.id] = ''
    })

    // Restore any previously auto-saved answers
    try {
      const saved = await api.get(`/exams/load-saved/${data.student_exam.id}`)
      const savedAnswers = saved.data.saved_answers || {}
      Object.entries(savedAnswers).forEach(([qid, ans]) => {
        if (ans !== null && ans !== undefined) answers.value[qid] = ans
      })
    } catch (_) {
      // Silent — restore is optional
    }

    phase.value = 'taking'
    await nextTick()
    attachAntiCheat()
    enterFullscreen()
    if (webcamStream && webcamMonitor.value) {
      webcamMonitor.value.srcObject = webcamStream
    }

    // Start auto-save every 30 seconds
    autoSaveTimer = setInterval(autoSave, AUTO_SAVE_INTERVAL)

  } catch (e) {
    // Show the exact message from the backend, not a generic string
    errorMsg.value = e.response?.data?.message
      || e.response?.data?.errors?.exam_id?.[0]
      || e.message
      || 'Could not start exam. Please try again.'
    phase.value = 'error'
    stopWebcam()
  } finally {
    starting.value = false
  }
}

// ── Submit ────────────────────────────────────────────────────────────────────
async function submitExam() {
  if (submitting.value) return
  submitting.value = true; showConfirm.value = false; showForceSubmit.value = false
  clearInterval(autoSaveTimer)
  detachAntiCheat()
  try {
    const payload = questions.value.map(q => ({ question_id: q.id, answer: answers.value[q.id] || '' }))
    const { data } = await api.post('/exams/submit', { student_exam_id: studentExamId.value, answers: payload })
    logActivity('exam_submitted', `violations=${violations.value}`)
    result.value = data.result
    phase.value  = 'result'
  } catch (e) {
    errorMsg.value = e.response?.data?.message || 'Submission failed.'
    phase.value    = 'error'
  } finally { submitting.value = false }
}
</script>
