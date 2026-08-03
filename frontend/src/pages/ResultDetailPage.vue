<template>
  <div>
    <router-link to="/results"
      class="inline-flex items-center gap-1.5 text-sm text-indigo-600 hover:text-indigo-800 mb-5">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
      </svg>
      Back to Results
    </router-link>

    <div v-if="loading" class="text-center py-20">
      <svg class="animate-spin w-8 h-8 text-indigo-500 mx-auto" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
      </svg>
    </div>

    <div v-else-if="result" class="space-y-5">
      <!-- Summary card -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <div class="flex items-start justify-between flex-wrap gap-4 mb-6">
          <div>
            <h1 class="text-xl font-bold text-gray-800 mb-1">{{ result.exam?.title }}</h1>
            <p class="text-indigo-600 text-sm font-medium">{{ result.exam?.course?.title }}</p>
            <p class="text-gray-500 text-sm mt-1">
              Student: <strong>{{ result.student?.user?.name }}</strong>
            </p>
          </div>
          <span :class="result.is_passed
              ? 'bg-green-100 text-green-700'
              : 'bg-red-100 text-red-700'"
            class="px-4 py-2 rounded-xl text-sm font-bold">
            {{ result.is_passed ? 'PASSED' : 'FAILED' }}
          </span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
          <div class="bg-gray-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-gray-800">{{ result.obtained_marks }}</p>
            <p class="text-xs text-gray-500 mt-1">Score</p>
          </div>
          <div class="bg-gray-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-gray-800">{{ result.total_marks }}</p>
            <p class="text-xs text-gray-500 mt-1">Total Marks</p>
          </div>
          <div class="bg-gray-50 rounded-xl p-4 text-center">
            <p :class="result.is_passed ? 'text-green-600' : 'text-red-600'"
              class="text-2xl font-bold">{{ result.percentage }}%</p>
            <p class="text-xs text-gray-500 mt-1">Percentage</p>
          </div>
          <div class="bg-gray-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-gray-800">
              {{ result.student_exam?.answers?.length || 0 }}
            </p>
            <p class="text-xs text-gray-500 mt-1">Answered</p>
          </div>
        </div>
      </div>

      <!-- Instructor comment card -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h2 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
          <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
          </svg>
          Instructor Comments
        </h2>

        <!-- View mode (student / read-only) -->
        <div v-if="!editingComment">
          <div v-if="result.remarks"
            class="bg-indigo-50 border border-indigo-100 rounded-xl px-4 py-3 text-sm text-gray-700">
            {{ result.remarks }}
          </div>
          <p v-else class="text-gray-400 text-sm italic">No comments added yet.</p>
          <button v-if="auth.isInstructor || auth.isAdmin"
            @click="startEdit"
            class="mt-3 text-xs text-indigo-600 hover:text-indigo-800 font-medium">
            {{ result.remarks ? 'Edit comment' : '+ Add comment' }}
          </button>
        </div>

        <!-- Edit mode (instructor / admin) -->
        <div v-else class="space-y-3">
          <textarea v-model="commentText" rows="3" maxlength="1000"
            placeholder="Write your feedback for this student…"
            class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm text-gray-800
                   focus:outline-none focus:ring-2 focus:ring-indigo-400 resize-none"/>
          <div class="flex gap-2">
            <button @click="saveComment" :disabled="savingComment"
              class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60
                     text-white text-sm font-semibold rounded-lg">
              {{ savingComment ? 'Saving…' : 'Save Comment' }}
            </button>
            <button @click="editingComment = false"
              class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 border border-gray-200 rounded-lg">
              Cancel
            </button>
          </div>
          <p v-if="commentError" class="text-red-600 text-xs">{{ commentError }}</p>
        </div>
      </div>

      <!-- Answer review -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-800">Answer Review</h2>
          <div class="flex items-center gap-3 text-xs">
            <span class="flex items-center gap-1.5">
              <span class="w-3 h-3 rounded-full bg-green-400 inline-block"/>Correct
            </span>
            <span class="flex items-center gap-1.5">
              <span class="w-3 h-3 rounded-full bg-red-400 inline-block"/>Wrong
            </span>
            <span class="flex items-center gap-1.5">
              <span class="w-3 h-3 rounded-full bg-gray-300 inline-block"/>Manual
            </span>
          </div>
        </div>
        <div class="divide-y divide-gray-50">
          <div v-if="!result.student_exam?.answers?.length"
            class="px-6 py-8 text-center text-gray-400 text-sm">
            No answer data available.
          </div>
          <div v-for="(answer, idx) in result.student_exam?.answers"
            :key="answer.id" class="p-5">
            <div class="flex items-start gap-3">
              <!-- Status icon -->
              <div :class="answer.is_correct === true
                  ? 'bg-green-100 text-green-600'
                  : answer.is_correct === false
                    ? 'bg-red-100 text-red-600'
                    : 'bg-gray-100 text-gray-500'"
                class="w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                <svg v-if="answer.is_correct === true" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <svg v-else-if="answer.is_correct === false" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                <span v-else class="text-xs font-bold">?</span>
              </div>

              <div class="flex-1 min-w-0">
                <p class="font-medium text-gray-800 text-sm mb-2">
                  <span class="text-gray-400 mr-1">{{ idx + 1 }}.</span>
                  {{ answer.question?.question_text }}
                </p>
                <div class="flex flex-wrap gap-3 text-xs">
                  <span class="bg-blue-50 text-blue-700 px-2.5 py-1 rounded-lg">
                    Your answer: <strong>{{ answer.answer || '(no answer)' }}</strong>
                  </span>
                  <span v-if="answer.question?.question_type !== 'short_answer'"
                    class="bg-green-50 text-green-700 px-2.5 py-1 rounded-lg">
                    Correct: <strong>{{ answer.question?.correct_answer }}</strong>
                  </span>
                </div>
              </div>

              <span class="flex-shrink-0 text-xs font-semibold text-indigo-600
                           bg-indigo-50 px-2 py-1 rounded-full whitespace-nowrap">
                {{ answer.marks_obtained }} / {{ answer.question?.marks }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import api from '@/axios'

const route          = useRoute()
const auth           = useAuthStore()
const result         = ref(null)
const loading        = ref(true)
const editingComment = ref(false)
const commentText    = ref('')
const savingComment  = ref(false)
const commentError   = ref('')

onMounted(async () => {
  try {
    const { data } = await api.get(`/results/${route.params.id}`)
    result.value = data
  } finally { loading.value = false }
})

function startEdit() {
  commentText.value    = result.value.remarks || ''
  commentError.value   = ''
  editingComment.value = true
}

async function saveComment() {
  savingComment.value = true
  commentError.value  = ''
  try {
    const { data } = await api.post(`/results/${result.value.id}/comment`, {
      remarks: commentText.value,
    })
    result.value.remarks = data.remarks
    editingComment.value = false
  } catch (e) {
    commentError.value = e.response?.data?.message || 'Failed to save comment.'
  } finally { savingComment.value = false }
}
</script>
