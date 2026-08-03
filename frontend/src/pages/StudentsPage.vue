<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Students</h1>
        <p class="text-gray-500 text-sm mt-1">Manage student accounts</p>
      </div>
      <button @click="openAdd"
        class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Add Student
      </button>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-5">
      <div class="relative max-w-xs">
        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input v-model="search" @input="debouncedFetch" placeholder="Search students..."
          class="w-full border border-gray-200 rounded-lg pl-9 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400" />
      </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Student</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">ID</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Email</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-if="loading"><td colspan="5" class="px-4 py-8 text-center text-gray-400">Loading...</td></tr>
            <tr v-else-if="!students.length"><td colspan="5" class="px-4 py-8 text-center text-gray-400">No students found.</td></tr>
            <tr v-else v-for="s in students" :key="s.id" class="hover:bg-gray-50">
              <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 font-bold text-xs flex items-center justify-center">
                    {{ initials(s.user?.name) }}
                  </div>
                  <span class="font-medium text-gray-800">{{ s.user?.name }}</span>
                </div>
              </td>
              <td class="px-4 py-3 text-gray-500">{{ s.student_id || '—' }}</td>
              <td class="px-4 py-3 text-gray-500">{{ s.user?.email }}</td>
              <td class="px-4 py-3">
                <span :class="s.user?.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                  class="px-2 py-0.5 rounded-full text-xs font-semibold">
                  {{ s.user?.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="px-4 py-3 flex gap-3">
                <button @click="openEdit(s)" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">Edit</button>
                <button @click="confirmDelete(s)" class="text-red-500 hover:text-red-700 text-xs font-medium">Delete</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <BaseModal v-model="showModal" :title="editingStudent ? 'Edit Student' : 'Add Student'" size="md">
      <form class="grid grid-cols-2 gap-4">
        <div><label class="form-label">Full Name *</label><input v-model="form.name" required class="input-field" /></div>
        <div><label class="form-label">Email *</label><input v-model="form.email" type="email" required class="input-field" /></div>
        <div><label class="form-label">{{ editingStudent ? 'New Password' : 'Password *' }}</label><input v-model="form.password" type="password" :required="!editingStudent" class="input-field" /></div>
        <div><label class="form-label">Student ID</label><input v-model="form.student_id" class="input-field" /></div>
        <div><label class="form-label">Phone</label><input v-model="form.phone" class="input-field" /></div>
        <div>
          <label class="form-label">Gender</label>
          <select v-model="form.gender" class="input-field">
            <option value="">— Select —</option>
            <option value="male">Male</option>
            <option value="female">Female</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div class="col-span-2"><label class="form-label">Address</label><input v-model="form.address" class="input-field" /></div>
        <p v-if="formError" class="col-span-2 text-red-600 text-xs">{{ formError }}</p>
      </form>
      <template #footer>
        <button @click="showModal = false" class="px-4 py-2 text-sm text-gray-600">Cancel</button>
        <button @click="saveStudent" :disabled="saving"
          class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white text-sm font-semibold rounded-lg">
          {{ saving ? 'Saving...' : (editingStudent ? 'Update' : 'Create') }}
        </button>
      </template>
    </BaseModal>
    <BaseModal v-model="showDeleteModal" title="Delete Student" size="sm">
      <p class="text-gray-600 text-sm">Delete <strong>{{ deletingStudent?.user?.name }}</strong>? This cannot be undone.</p>
      <template #footer>
        <button @click="showDeleteModal = false" class="px-4 py-2 text-sm text-gray-600">Cancel</button>
        <button @click="deleteStudent" :disabled="saving"
          class="px-5 py-2 bg-red-600 hover:bg-red-700 disabled:opacity-60 text-white text-sm font-semibold rounded-lg">
          {{ saving ? 'Deleting...' : 'Delete' }}
        </button>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/axios'
import BaseModal from '@/components/BaseModal.vue'

const students        = ref([])
const loading         = ref(true)
const search          = ref('')
const showModal       = ref(false)
const showDeleteModal = ref(false)
const editingStudent  = ref(null)
const deletingStudent = ref(null)
const saving          = ref(false)
const formError       = ref('')
const form = ref({ name:'', email:'', password:'', student_id:'', phone:'', gender:'', address:'' })

let debounce
function debouncedFetch() { clearTimeout(debounce); debounce = setTimeout(fetchStudents, 350) }

async function fetchStudents() {
  loading.value = true
  try { const { data } = await api.get('/students', { params: { search: search.value } }); students.value = data.data }
  finally { loading.value = false }
}

function initials(n) { return (n||'U').split(' ').map(w=>w[0]).slice(0,2).join('').toUpperCase() }

function openAdd() {
  editingStudent.value = null
  form.value = { name:'', email:'', password:'', student_id:'', phone:'', gender:'', address:'' }
  formError.value = ''; showModal.value = true
}

function openEdit(s) {
  editingStudent.value = s
  form.value = { name: s.user?.name||'', email: s.user?.email||'', password:'',
    student_id: s.student_id||'', phone: s.phone||'', gender: s.gender||'', address: s.address||'' }
  formError.value = ''; showModal.value = true
}

async function saveStudent() {
  saving.value = true; formError.value = ''
  try {
    const p = { ...form.value }; if (!p.password) delete p.password
    editingStudent.value ? await api.put(`/students/${editingStudent.value.id}`, p) : await api.post('/students', p)
    showModal.value = false; fetchStudents()
  } catch (e) {
    formError.value = e.response?.data?.message || Object.values(e.response?.data?.errors||{})[0]?.[0] || 'Error'
  } finally { saving.value = false }
}

function confirmDelete(s) { deletingStudent.value = s; showDeleteModal.value = true }
async function deleteStudent() {
  saving.value = true
  try { await api.delete(`/students/${deletingStudent.value.id}`); showDeleteModal.value = false; fetchStudents() }
  finally { saving.value = false }
}

onMounted(fetchStudents)
</script>
