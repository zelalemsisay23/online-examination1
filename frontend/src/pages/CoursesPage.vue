<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <div><h1 class="text-2xl font-bold text-gray-800">Courses</h1><p class="text-gray-500 text-sm mt-1">Manage course catalog</p></div>
      <button v-if="auth.isAdmin" @click="openAdd" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Add Course
      </button>
    </div>
    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      <div v-for="i in 3" :key="i" class="bg-white rounded-xl h-36 animate-pulse border border-gray-100"/>
    </div>
    <div v-else-if="!courses.length" class="bg-white rounded-xl border border-gray-100 shadow-sm p-12 text-center text-gray-400">No courses found.</div>
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      <div v-for="c in courses" :key="c.id" class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between mb-2">
          <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-sm">{{ c.code?.slice(0,2) }}</div>
          <span :class="c.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'" class="px-2 py-0.5 rounded-full text-xs font-semibold">{{ c.is_active ? 'Active' : 'Inactive' }}</span>
        </div>
        <h3 class="font-semibold text-gray-800 mt-3 mb-1">{{ c.title }}</h3>
        <p class="text-xs text-indigo-600 font-medium mb-2">{{ c.code }}</p>
        <p class="text-xs text-gray-500 line-clamp-2 mb-3">{{ c.description || 'No description.' }}</p>
        <p class="text-xs text-gray-500"><span class="font-medium text-gray-700">Instructor:</span> {{ c.instructor?.user?.name || 'Unassigned' }}</p>
        <div v-if="auth.isAdmin" class="flex gap-3 mt-4 pt-3 border-t border-gray-100">
          <button @click="openEdit(c)" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">Edit</button>
          <button @click="confirmDelete(c)" class="text-red-500 hover:text-red-700 text-xs font-medium">Delete</button>
        </div>
      </div>
    </div>
    <BaseModal v-model="showModal" :title="editing ? 'Edit Course' : 'Add Course'" size="md">
      <form class="grid grid-cols-2 gap-4">
        <div class="col-span-2"><label class="form-label">Course Title *</label><input v-model="form.title" required class="input-field"/></div>
        <div><label class="form-label">Course Code *</label><input v-model="form.code" required class="input-field" placeholder="CS101"/></div>
        <div>
          <label class="form-label">Instructor</label>
          <select v-model="form.instructor_id" class="input-field">
            <option value="">— None —</option>
            <option v-for="i in instructorsList" :key="i.id" :value="i.id">{{ i.user?.name }}</option>
          </select>
        </div>
        <div class="col-span-2"><label class="form-label">Description</label><textarea v-model="form.description" rows="2" class="input-field resize-none"/></div>
        <div class="col-span-2 flex items-center gap-2"><input v-model="form.is_active" type="checkbox" id="active" class="accent-indigo-600"/><label for="active" class="text-sm text-gray-700">Active</label></div>
        <p v-if="formError" class="col-span-2 text-red-600 text-xs">{{ formError }}</p>
      </form>
      <template #footer>
        <button @click="showModal = false" class="px-4 py-2 text-sm text-gray-600">Cancel</button>
        <button @click="save" :disabled="saving" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white text-sm font-semibold rounded-lg">{{ saving ? 'Saving...' : (editing ? 'Update' : 'Create') }}</button>
      </template>
    </BaseModal>
    <BaseModal v-model="showDeleteModal" title="Delete Course" size="sm">
      <p class="text-gray-600 text-sm">Delete <strong>{{ deleting?.title }}</strong>?</p>
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
const courses = ref([]); const instructorsList = ref([]); const loading = ref(true)
const showModal = ref(false); const showDeleteModal = ref(false)
const editing = ref(null); const deleting = ref(null)
const saving = ref(false); const formError = ref('')
const form = ref({ title:'', code:'', description:'', instructor_id:'', is_active:true })
async function fetchCourses() { loading.value = true; try { const { data } = await api.get('/courses'); courses.value = data.data } finally { loading.value = false } }
async function loadInstructors() { const { data } = await api.get('/instructors', { params: { per_page: 100 } }); instructorsList.value = data.data }
function openAdd() { editing.value = null; form.value = { title:'', code:'', description:'', instructor_id:'', is_active:true }; formError.value = ''; showModal.value = true }
function openEdit(c) { editing.value = c; form.value = { title: c.title, code: c.code, description: c.description||'', instructor_id: c.instructor_id||'', is_active: c.is_active }; formError.value = ''; showModal.value = true }
async function save() {
  saving.value = true; formError.value = ''
  try { editing.value ? await api.put(`/courses/${editing.value.id}`, form.value) : await api.post('/courses', form.value); showModal.value = false; fetchCourses() }
  catch (e) { formError.value = e.response?.data?.message || Object.values(e.response?.data?.errors||{})[0]?.[0] || 'Error' }
  finally { saving.value = false }
}
function confirmDelete(c) { deleting.value = c; showDeleteModal.value = true }
async function doDelete() { saving.value = true; try { await api.delete(`/courses/${deleting.value.id}`); showDeleteModal.value = false; fetchCourses() } finally { saving.value = false } }
onMounted(() => { fetchCourses(); loadInstructors() })
</script>
