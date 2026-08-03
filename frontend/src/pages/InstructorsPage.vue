<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <div><h1 class="text-2xl font-bold text-gray-800">Instructors</h1><p class="text-gray-500 text-sm mt-1">Manage instructor accounts</p></div>
      <button @click="openAdd" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Add Instructor
      </button>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-5">
      <div class="relative max-w-xs">
        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input v-model="search" @input="debouncedFetch" placeholder="Search instructors..." class="w-full border border-gray-200 rounded-lg pl-9 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400" />
      </div>
    </div>
    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      <div v-for="i in 3" :key="i" class="bg-white rounded-xl h-36 animate-pulse border border-gray-100" />
    </div>
    <div v-else-if="!instructors.length" class="bg-white rounded-xl border border-gray-100 shadow-sm p-12 text-center text-gray-400">No instructors found.</div>
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      <div v-for="inst in instructors" :key="inst.id" class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between mb-3">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-purple-100 text-purple-700 font-bold text-sm flex items-center justify-center">{{ initials(inst.user?.name) }}</div>
            <div><p class="font-semibold text-gray-800 text-sm">{{ inst.user?.name }}</p><p class="text-xs text-gray-500">{{ inst.user?.email }}</p></div>
          </div>
          <span :class="inst.user?.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'" class="px-2 py-0.5 rounded-full text-xs font-semibold">
            {{ inst.user?.is_active ? 'Active' : 'Inactive' }}
          </span>
        </div>
        <div class="space-y-1 text-xs text-gray-500">
          <p v-if="inst.department"><span class="font-medium text-gray-700">Dept:</span> {{ inst.department }}</p>
          <p v-if="inst.specialization"><span class="font-medium text-gray-700">Spec:</span> {{ inst.specialization }}</p>
        </div>
        <div class="flex gap-3 mt-4 pt-3 border-t border-gray-100">
          <button @click="openEdit(inst)" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">Edit</button>
          <button @click="confirmDelete(inst)" class="text-red-500 hover:text-red-700 text-xs font-medium">Delete</button>
        </div>
      </div>
    </div>
    <BaseModal v-model="showModal" :title="editing ? 'Edit Instructor' : 'Add Instructor'" size="md">
      <form class="grid grid-cols-2 gap-4">
        <div><label class="form-label">Full Name *</label><input v-model="form.name" required class="input-field" /></div>
        <div><label class="form-label">Email *</label><input v-model="form.email" type="email" required class="input-field" /></div>
        <div><label class="form-label">{{ editing ? 'New Password' : 'Password *' }}</label><input v-model="form.password" type="password" :required="!editing" class="input-field" /></div>
        <div><label class="form-label">Employee ID</label><input v-model="form.employee_id" class="input-field" /></div>
        <div><label class="form-label">Department</label><input v-model="form.department" class="input-field" /></div>
        <div><label class="form-label">Specialization</label><input v-model="form.specialization" class="input-field" /></div>
        <p v-if="formError" class="col-span-2 text-red-600 text-xs">{{ formError }}</p>
      </form>
      <template #footer>
        <button @click="showModal = false" class="px-4 py-2 text-sm text-gray-600">Cancel</button>
        <button @click="save" :disabled="saving" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white text-sm font-semibold rounded-lg">{{ saving ? 'Saving...' : (editing ? 'Update' : 'Create') }}</button>
      </template>
    </BaseModal>
    <BaseModal v-model="showDeleteModal" title="Delete Instructor" size="sm">
      <p class="text-gray-600 text-sm">Delete <strong>{{ deleting?.user?.name }}</strong>?</p>
      <template #footer>
        <button @click="showDeleteModal = false" class="px-4 py-2 text-sm text-gray-600">Cancel</button>
        <button @click="doDelete" :disabled="saving" class="px-5 py-2 bg-red-600 hover:bg-red-700 disabled:opacity-60 text-white text-sm font-semibold rounded-lg">{{ saving ? 'Deleting...' : 'Delete' }}</button>
      </template>
    </BaseModal>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import api from '@/axios'
import BaseModal from '@/components/BaseModal.vue'
const instructors = ref([]); const loading = ref(true); const search = ref('')
const showModal = ref(false); const showDeleteModal = ref(false)
const editing = ref(null); const deleting = ref(null)
const saving = ref(false); const formError = ref('')
const form = ref({ name:'', email:'', password:'', employee_id:'', department:'', specialization:'' })
let debounce
function debouncedFetch() { clearTimeout(debounce); debounce = setTimeout(fetchData, 350) }
async function fetchData() {
  loading.value = true
  try { const { data } = await api.get('/instructors', { params: { search: search.value } }); instructors.value = data.data }
  finally { loading.value = false }
}
function initials(n) { return (n||'U').split(' ').map(w=>w[0]).slice(0,2).join('').toUpperCase() }
function openAdd() { editing.value = null; form.value = { name:'', email:'', password:'', employee_id:'', department:'', specialization:'' }; formError.value = ''; showModal.value = true }
function openEdit(i) { editing.value = i; form.value = { name: i.user?.name||'', email: i.user?.email||'', password:'', employee_id: i.employee_id||'', department: i.department||'', specialization: i.specialization||'' }; formError.value = ''; showModal.value = true }
async function save() {
  saving.value = true; formError.value = ''
  try { const p = {...form.value}; if (!p.password) delete p.password; editing.value ? await api.put(`/instructors/${editing.value.id}`, p) : await api.post('/instructors', p); showModal.value = false; fetchData() }
  catch (e) { formError.value = e.response?.data?.message || Object.values(e.response?.data?.errors||{})[0]?.[0] || 'Error' }
  finally { saving.value = false }
}
function confirmDelete(i) { deleting.value = i; showDeleteModal.value = true }
async function doDelete() { saving.value = true; try { await api.delete(`/instructors/${deleting.value.id}`); showDeleteModal.value = false; fetchData() } finally { saving.value = false } }
onMounted(fetchData)
</script>
