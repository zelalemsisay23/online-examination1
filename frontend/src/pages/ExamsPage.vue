<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <div><h1 class="text-2xl font-bold text-gray-800">Exams</h1><p class="text-gray-500 text-sm mt-1">{{ auth.isStudent ? 'Available exams' : 'Manage examinations' }}</p></div>
      <button v-if="!auth.isStudent" @click="openAdd" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Create Exam
      </button>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-5 flex gap-3 flex-wrap">
      <div class="relative">
        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input v-model="search" @input="debouncedFetch" placeholder="Search exams..." class="border border-gray-200 rounded-lg pl-9 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 w-56"/>
      </div>
      <select v-if="!auth.isStudent" v-model="statusFilter" @change="fetchExams()" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
        <option value="">All Statuses</option><option value="draft">Draft</option><option value="active">Active</option><option value="completed">Completed</option>
      </select>
    </div>
    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      <div v-for="i in 6" :key="i" class="bg-white rounded-xl h-48 animate-pulse border border-gray-100"/>
    </div>
    <div v-else-if="!exams.length" class="bg-white rounded-xl border border-gray-100 shadow-sm p-16 text-center text-gray-400">No exams found.</div>
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      <div v-for="exam in exams" :key="exam.id" class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 flex flex-col hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between mb-3">
          <span :class="statusClass(exam.status)" class="px-2.5 py-1 rounded-full text-xs font-semibold capitalize">{{ exam.status }}</span>
          <span class="text-xs text-gray-400">{{ exam.questions_count }} questions</span>
        </div>
        <h3 class="font-semibold text-gray-800 mb-1 line-clamp-2">{{ exam.title }}</h3>
        <p class="text-xs text-indigo-600 font-medium mb-2">{{ exam.course?.title }}</p>
        <p class="text-xs text-gray-500 flex-1 line-clamp-2 mb-4">{{ exam.description || 'No description.' }}</p>
        <div class="grid grid-cols-2 gap-2 text-xs text-gray-500 mb-4">
          <div>⏱ {{ exam.duration }} min</div>
          <div>✓ Pass: {{ exam.passing_marks }}/{{ exam.total_marks }}</div>
          <div class="col-span-2">📅 {{ fmtDate(exam.start_date) }}</div>
        </div>
        <div class="pt-3 border-t border-gray-100 flex gap-2">
          <template v-if="auth.isStudent">
            <router-link :to="`/take-exam/${exam.id}`" class="flex-1 text-center bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold py-2 rounded-lg">Take Exam</router-link>
          </template>
          <template v-else>
            <router-link :to="`/exams/${exam.id}`" class="flex-1 text-center bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold py-2 rounded-lg">Manage</router-link>
            <button @click="confirmDelete(exam)" class="px-3 py-2 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
          </template>
        </div>
      </div>
    </div>
    <BaseModal v-model="showModal" :title="editing ? 'Edit Exam' : 'Create Exam'" size="lg">
      <form class="grid grid-cols-2 gap-4">
        <div class="col-span-2"><label class="form-label">Exam Title *</label><input v-model="form.title" required class="input-field"/></div>
        <div><label class="form-label">Course *</label><select v-model="form.course_id" required class="input-field"><option value="">— Select —</option><option v-for="c in coursesList" :key="c.id" :value="c.id">{{ c.title }}</option></select></div>
        <div><label class="form-label">Status</label><select v-model="form.status" class="input-field"><option value="draft">Draft</option><option value="active">Active</option><option value="completed">Completed</option></select></div>
        <div><label class="form-label">Start Date *</label><input v-model="form.start_date" type="datetime-local" required class="input-field"/></div>
        <div><label class="form-label">End Date *</label><input v-model="form.end_date" type="datetime-local" required class="input-field"/></div>
        <div><label class="form-label">Duration (min) *</label><input v-model.number="form.duration" type="number" min="1" required class="input-field"/></div>
        <div><label class="form-label">Passing Marks *</label><input v-model.number="form.passing_marks" type="number" min="0" required class="input-field"/></div>
        <div class="col-span-2"><label class="form-label">Description</label><textarea v-model="form.description" rows="2" class="input-field resize-none"/></div>
        <div class="col-span-2 flex items-center gap-2"><input v-model="form.shuffle_questions" type="checkbox" id="shuffle" class="accent-indigo-600"/><label for="shuffle" class="text-sm text-gray-700">Shuffle questions</label></div>
        <p v-if="formError" class="col-span-2 text-red-600 text-xs">{{ formError }}</p>
      </form>
      <template #footer>
        <button @click="showModal = false" class="px-4 py-2 text-sm text-gray-600">Cancel</button>
        <button @click="save" :disabled="saving" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white text-sm font-semibold rounded-lg">{{ saving ? 'Saving...' : (editing ? 'Update' : 'Create') }}</button>
      </template>
    </BaseModal>
    <BaseModal v-model="showDeleteModal" title="Delete Exam" size="sm">
      <p class="text-gray-600 text-sm">Delete exam <strong>{{ deletingExam?.title }}</strong>? All questions and results will be removed.</p>
      <template #footer>
        <button @click="showDeleteModal = false" class="px-4 py-2 text-sm text-gray-600">Cancel</button>
        <button @click="doDelete" :disabled="saving" class="px-5 py-2 bg-red-600 hover:bg-red-700 disabled:opacity-60 text-white text-sm font-semibold rounded-lg">{{ saving ? 'Deleting...' : 'Delete' }}</button>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/axios'
import BaseModal from '@/components/BaseModal.vue'
const auth = useAuthStore()
const exams = ref([]); const coursesList = ref([]); const loading = ref(true)
const search = ref(''); const statusFilter = ref('')
const showModal = ref(false); const showDeleteModal = ref(false)
const editing = ref(null); const deletingExam = ref(null)
const saving = ref(false); const formError = ref('')
const blank = () => ({ title:'', course_id:'', description:'', start_date:'', end_date:'', duration:60, passing_marks:0, status:'draft', shuffle_questions:false })
const form = ref(blank())
let debounce
function debouncedFetch() { clearTimeout(debounce); debounce = setTimeout(fetchExams, 350) }
async function fetchExams() {
  loading.value = true
  try {
    const params = { search: search.value }
    if (statusFilter.value) params.status = statusFilter.value
    const { data } = await api.get('/exams', { params })
    exams.value = data.data
  }
  finally { loading.value = false }
}
async function loadCourses() { const { data } = await api.get('/courses', { params: { per_page:100 } }); coursesList.value = data.data }
function statusClass(s) { return { draft:'bg-gray-100 text-gray-600', active:'bg-green-100 text-green-700', completed:'bg-blue-100 text-blue-700' }[s] || 'bg-gray-100 text-gray-600' }
function fmtDate(d) { return d ? new Date(d).toLocaleDateString('en-US', { month:'short', day:'numeric', year:'numeric' }) : '—' }
function openAdd() { editing.value = null; form.value = blank(); formError.value = ''; showModal.value = true }
function openEdit(e) { editing.value = e; form.value = { title:e.title, course_id:e.course_id, description:e.description||'', start_date:e.start_date?.slice(0,16)||'', end_date:e.end_date?.slice(0,16)||'', duration:e.duration, passing_marks:e.passing_marks, status:e.status, shuffle_questions:e.shuffle_questions }; formError.value = ''; showModal.value = true }
async function save() {
  saving.value = true; formError.value = ''
  try { editing.value ? await api.put(`/exams/${editing.value.id}`, form.value) : await api.post('/exams', form.value); showModal.value = false; fetchExams() }
  catch (e) { formError.value = e.response?.data?.message || Object.values(e.response?.data?.errors||{})[0]?.[0] || 'Error' }
  finally { saving.value = false }
}
function confirmDelete(e) { deletingExam.value = e; showDeleteModal.value = true }
async function doDelete() { saving.value = true; try { await api.delete(`/exams/${deletingExam.value.id}`); showDeleteModal.value = false; fetchExams() } finally { saving.value = false } }
onMounted(() => { fetchExams(); loadCourses() })
</script>
