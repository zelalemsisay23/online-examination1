<template>
  <div class="max-w-2xl mx-auto">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-800">Profile</h1>
      <p class="text-gray-500 text-sm mt-1">Manage your account settings</p>
    </div>

    <!-- Profile card -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mb-5">
      <div class="flex items-center gap-5 mb-6">
        <div class="w-16 h-16 rounded-2xl bg-indigo-600 text-white font-bold text-xl flex items-center justify-center">
          {{ initials }}
        </div>
        <div>
          <h2 class="text-lg font-bold text-gray-800">{{ auth.user?.name }}</h2>
          <p class="text-sm text-gray-500">{{ auth.user?.email }}</p>
          <span class="inline-block mt-1 px-2.5 py-0.5 bg-indigo-100 text-indigo-700 text-xs font-semibold rounded-full capitalize">
            {{ auth.user?.role }}
          </span>
        </div>
      </div>
      <div v-if="profile" class="grid grid-cols-2 gap-4 text-sm">
        <template v-if="auth.isStudent">
          <div><span class="text-gray-500">Student ID:</span> <span class="font-medium text-gray-800">{{ profile.student_id || '—' }}</span></div>
          <div><span class="text-gray-500">Phone:</span> <span class="font-medium text-gray-800">{{ profile.phone || '—' }}</span></div>
          <div><span class="text-gray-500">Gender:</span> <span class="font-medium text-gray-800 capitalize">{{ profile.gender || '—' }}</span></div>
          <div><span class="text-gray-500">Date of Birth:</span> <span class="font-medium text-gray-800">{{ profile.date_of_birth || '—' }}</span></div>
          <div class="col-span-2"><span class="text-gray-500">Address:</span> <span class="font-medium text-gray-800">{{ profile.address || '—' }}</span></div>
        </template>
        <template v-else-if="auth.isInstructor">
          <div><span class="text-gray-500">Employee ID:</span> <span class="font-medium text-gray-800">{{ profile.employee_id || '—' }}</span></div>
          <div><span class="text-gray-500">Phone:</span> <span class="font-medium text-gray-800">{{ profile.phone || '—' }}</span></div>
          <div><span class="text-gray-500">Department:</span> <span class="font-medium text-gray-800">{{ profile.department || '—' }}</span></div>
          <div><span class="text-gray-500">Specialization:</span> <span class="font-medium text-gray-800">{{ profile.specialization || '—' }}</span></div>
        </template>
        <template v-else>
          <div class="col-span-2"><span class="text-gray-500">Role:</span> <span class="font-medium text-gray-800">System Administrator</span></div>
        </template>
      </div>
    </div>

    <!-- Change password -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
      <h3 class="text-base font-semibold text-gray-800 mb-5">Change Password</h3>
      <div v-if="pwSuccess" class="mb-4 bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3 rounded-lg">
        Password changed successfully.
      </div>
      <div v-if="pwError" class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg">
        {{ pwError }}
      </div>
      <form @submit.prevent="changePassword" class="space-y-4">
        <div><label class="form-label">Current Password</label><input v-model="pw.current" type="password" required class="input-field" placeholder="••••••••"/></div>
        <div><label class="form-label">New Password</label><input v-model="pw.newPass" type="password" required minlength="8" class="input-field" placeholder="••••••••"/></div>
        <div><label class="form-label">Confirm New Password</label><input v-model="pw.confirm" type="password" required class="input-field" placeholder="••••••••"/></div>
        <button type="submit" :disabled="pwSaving" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white text-sm font-semibold rounded-lg">
          {{ pwSaving ? 'Saving...' : 'Update Password' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/axios'

const auth     = useAuthStore()
const profile  = ref(null)
const pw       = ref({ current: '', newPass: '', confirm: '' })
const pwSaving  = ref(false)
const pwError   = ref('')
const pwSuccess = ref(false)

const initials = computed(() =>
  (auth.user?.name || 'U').split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase()
)

onMounted(async () => {
  try {
    const { data } = await api.get('/me')
    if (data.student)    profile.value = data.student
    if (data.instructor) profile.value = data.instructor
  } catch (_) {}
})

async function changePassword() {
  if (pw.value.newPass !== pw.value.confirm) { pwError.value = 'New passwords do not match.'; return }
  pwSaving.value = true; pwError.value = ''; pwSuccess.value = false
  try {
    await auth.changePassword(pw.value.current, pw.value.newPass, pw.value.confirm)
    pwSuccess.value = true
    pw.value = { current: '', newPass: '', confirm: '' }
  } catch (e) {
    pwError.value = e.response?.data?.message || Object.values(e.response?.data?.errors || {})[0]?.[0] || 'Failed to change password.'
  } finally { pwSaving.value = false }
}
</script>
