<template>
  <div>
    <router-link to="/exams" class="inline-flex items-center gap-1.5 text-sm text-indigo-600 hover:text-indigo-800 mb-5">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg> Back to Exams
    </router-link>
    <div v-if="loadingExam" class="text-center py-16 text-gray-400">Loading exam...</div>
    <div v-else-if="exam">
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 mb-6">
        <div class="flex items-start justify-between flex-wrap gap-4">
          <div>
            <div class="flex items-center gap-3 mb-2">
              <h1 class="text-2xl font-bold text-gray-800">{{ exam.title }}</h1>
              <span :class="statusClass(exam.status)" class="px-2.5 py-1 rounded-full text-xs font-semibold capitalize">{{ exam.status }}</span>
            </div>
            <p class="text-indigo-600 font-medium text-sm">{{ exam.course?.title }}</p>
          </div>
          <button @click="openEditExam" class="px-4 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-sm font-medium rounded-lg">Edit Exam</button>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-5 pt-5 border-t border-gray-100">
          <div class="text-center"><p class="text-2xl font-bold text-gray-800">{{ exam.duration }}</p><p class="text-xs text-gray-500">Minutes</p></div>
          <div class="text-center"><p class="text-2xl font-bold text-gray-800">{{ exam.total_marks }}</p><p class="text-xs text-gray-500">Total Marks</p></div>
          <div class="text-center"><p class="text-2xl font-bold text-gray-800">{{ exam.passing_marks }}</p><p class="text-xs text-gray-500">Passing Marks</p></div>
          <div class="text-center"><p class="text-2xl font-bold text-gray-800">{{ exam.questions?.length || 0 }}</p><p class="text-xs text-gray-500">Questions</p></div>
        </div>
      </div>
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
          <h2 class="text-lg font-semibold text-gray-800">Questions</h2>
          <button @click="openAddQuestion" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-lg flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg> Add Question
          </button>
        </div>
        <div v-if="!exam.questions?.length" class="p-12 text-center text-gray-400">No questions yet.</div>
        <div v-else class="divide-y divide-gray-50">
          <div v-for="(q, idx) in exam.questions" :key="q.id" class="p-5 hover:bg-gray-50">
            <div class="flex items-start justify-between gap-4">
              <div class="flex items-start gap-3 flex-1">
                <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">{{ idx+1 }}</span>
                <div class="flex-1">
                  <p class="text-gray-800 font-medium text-sm mb-2">{{ q.question_text }}</p>
                  <span :class="typeClass(q.question_type)" class="text-xs px-2 py-0.5 rounded-full font-medium">{{ typeLabel(q.question_type) }}</span>
                  <div v-if="q.options?.length" class="mt-2 flex flex-wrap gap-2">
                    <span v-for="opt in q.options" :key="opt"
                      :class="opt===q.correct_answer ? 'bg-green-100 text-green-700 border-green-200' : 'bg-gray-100 text-gray-600 border-gray-200'"
                      class="text-xs px-2.5 py-1 rounded-lg border font-medium">
                      {{ opt }}<span v-if="opt===q.correct_answer"> ✓</span>
                    </span>
                  </div>
                </div>
              </div>
              <div class="flex items-center gap-3 flex-shrink-0">
                <span class="text-xs font-semibold text-indigo-600 bg-indigo-50 px-2 py-1 rounded-full">{{ q.marks }} mark{{ q.marks!==1?'s':'' }}</span>
                <button @click="openEditQuestion(q)" class="text-gray-400 hover:text-indigo-600">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </button>
                <button @click="deleteQuestion(q)" class="text-gray-400 hover:text-red-500">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <BaseModal v-model="showQModal" :title="editingQ ? 'Edit Question' : 'Add Question'" size="lg">
      <form class="space-y-4">
        <div><label class="form-label">Question Type *</label><select v-model="qForm.question_type" class="input-field"><option value="mcq">Multiple Choice (MCQ)</option><option value="true_false">True / False</option><option value="short_answer">Short Answer</option></select></div>
        <div><label class="form-label">Question Text *</label><textarea v-model="qForm.question_text" rows="3" required class="input-field resize-none"/></div>
        <div v-if="qForm.question_type==='mcq'">
          <label class="form-label">Options</label>
          <div class="space-y-2">
            <div v-for="(opt, i) in qForm.options" :key="i" class="flex gap-2">
              <input v-model="qForm.options[i]" class="input-field" :placeholder="`Option ${i+1}`"/>
              <button type="button" @click="qForm.options.splice(i,1)" class="text-red-400 hover:text-red-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>
            <button type="button" @click="qForm.options.push('')" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">+ Add Option</button>
          </div>
        </div>
        <div>
          <label class="form-label">Correct Answer *</label>
          <select v-if="qForm.question_type==='mcq' && qForm.options.filter(Boolean).length" v-model="qForm.correct_answer" class="input-field"><option value="">— Select —</option><option v-for="opt in qForm.options.filter(Boolean)" :key="opt" :value="opt">{{ opt }}</option></select>
          <select v-else-if="qForm.question_type==='true_false'" v-model="qForm.correct_answer" class="input-field"><option value="True">True</option><option value="False">False</option></select>
          <input v-else v-model="qForm.correct_answer" class="input-field" placeholder="Expected answer..."/>
        </div>
        <div><label class="form-label">Marks *</label><input v-model.number="qForm.marks" type="number" min="1" class="input-field w-28"/></div>
        <p v-if="qFormError" class="text-red-600 text-xs">{{ qFormError }}</p>
      </form>
      <template #footer>
        <button @click="showQModal=false" class="px-4 py-2 text-sm text-gray-600">Cancel</button>
        <button @click="saveQuestion" :disabled="savingQ" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white text-sm font-semibold rounded-lg">{{ savingQ ? 'Saving...' : (editingQ ? 'Update' : 'Add') }}</button>
      </template>
    </BaseModal>
    <BaseModal v-model="showEditExam" title="Edit Exam" size="lg">
      <form class="grid grid-cols-2 gap-4">
        <div class="col-span-2"><label class="form-label">Title</label><input v-model="examForm.title" class="input-field"/></div>
        <div><label class="form-label">Status</label><select v-model="examForm.status" class="input-field"><option value="draft">Draft</option><option value="active">Active</option><option value="completed">Completed</option></select></div>
        <div><label class="form-label">Duration (min)</label><input v-model.number="examForm.duration" type="number" class="input-field"/></div>
        <div><label class="form-label">Start Date</label><input v-model="examForm.start_date" type="datetime-local" class="input-field"/></div>
        <div><label class="form-label">End Date</label><input v-model="examForm.end_date" type="datetime-local" class="input-field"/></div>
        <div><label class="form-label">Passing Marks</label><input v-model.number="examForm.passing_marks" type="number" class="input-field"/></div>
        <div class="col-span-2"><label class="form-label">Description</label><textarea v-model="examForm.description" rows="2" class="input-field resize-none"/></div>
      </form>
      <template #footer>
        <button @click="showEditExam=false" class="px-4 py-2 text-sm text-gray-600">Cancel</button>
        <button @click="saveExam" :disabled="savingExam" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white text-sm font-semibold rounded-lg">{{ savingExam ? 'Saving...' : 'Update' }}</button>
      </template>
    </BaseModal>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/axios'
import BaseModal from '@/components/BaseModal.vue'
const route = useRoute()
const exam = ref(null); const loadingExam = ref(true)
const showQModal = ref(false); const showEditExam = ref(false)
const editingQ = ref(null); const savingQ = ref(false); const savingExam = ref(false); const qFormError = ref('')
const blankQ = () => ({ question_type:'mcq', question_text:'', options:['','','',''], correct_answer:'', marks:1 })
const qForm = ref(blankQ()); const examForm = ref({})
async function loadExam() { loadingExam.value = true; try { const { data } = await api.get(`/exams/${route.params.id}`); exam.value = data } finally { loadingExam.value = false } }
function statusClass(s) { return { draft:'bg-gray-100 text-gray-600', active:'bg-green-100 text-green-700', completed:'bg-blue-100 text-blue-700' }[s] }
function typeClass(t) { return { mcq:'bg-indigo-100 text-indigo-700', true_false:'bg-yellow-100 text-yellow-700', short_answer:'bg-purple-100 text-purple-700' }[t] }
function typeLabel(t) { return { mcq:'MCQ', true_false:'True / False', short_answer:'Short Answer' }[t] || t }
function openAddQuestion() { editingQ.value = null; qForm.value = blankQ(); qFormError.value = ''; showQModal.value = true }
function openEditQuestion(q) { editingQ.value = q; qForm.value = { question_type:q.question_type, question_text:q.question_text, options:q.options?[...q.options]:['','','',''], correct_answer:q.correct_answer, marks:q.marks }; qFormError.value = ''; showQModal.value = true }
async function saveQuestion() {
  savingQ.value = true; qFormError.value = ''
  try { const p = { ...qForm.value, options: qForm.value.options.filter(Boolean) }; editingQ.value ? await api.put(`/exams/${exam.value.id}/questions/${editingQ.value.id}`, p) : await api.post(`/exams/${exam.value.id}/questions`, p); showQModal.value = false; loadExam() }
  catch (e) { qFormError.value = e.response?.data?.message || Object.values(e.response?.data?.errors||{})[0]?.[0] || 'Error' }
  finally { savingQ.value = false }
}
async function deleteQuestion(q) { if (!confirm('Delete this question?')) return; await api.delete(`/exams/${exam.value.id}/questions/${q.id}`); loadExam() }
function openEditExam() { examForm.value = { title:exam.value.title, description:exam.value.description||'', status:exam.value.status, duration:exam.value.duration, passing_marks:exam.value.passing_marks, start_date:exam.value.start_date?.slice(0,16)||'', end_date:exam.value.end_date?.slice(0,16)||'' }; showEditExam.value = true }
async function saveExam() { savingExam.value = true; try { await api.put(`/exams/${exam.value.id}`, examForm.value); showEditExam.value = false; loadExam() } finally { savingExam.value = false } }
onMounted(loadExam)
</script>
