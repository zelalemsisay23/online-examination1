<template>
  <div class="min-h-screen bg-gradient-to-br from-indigo-900 via-indigo-800 to-purple-900 flex items-center justify-center p-4">
    <div class="w-full max-w-md">

      <!-- Header -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-white rounded-2xl shadow-lg mb-4">
          <svg class="w-9 h-9 text-indigo-700" fill="currentColor" viewBox="0 0 20 20">
            <path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969
              7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255
              0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1
              1 0 11-2 0V4.804z"/>
          </svg>
        </div>
        <h1 class="text-3xl font-bold text-white">Online Examination System</h1>
        <p class="text-indigo-300 text-sm mt-2">Select your role and sign in</p>
      </div>

      <!-- Card -->
      <div class="bg-white rounded-2xl shadow-2xl p-8">

        <!-- Role tabs -->
        <div class="grid grid-cols-3 gap-2 mb-6">
          <button v-for="role in roles" :key="role.key"
            @click="selectRole(role)"
            :class="[
              'flex flex-col items-center gap-1.5 py-3 px-2 rounded-xl border-2 transition-all text-sm font-semibold',
              selectedRole === role.key
                ? 'border-indigo-500 bg-indigo-50 text-indigo-700'
                : 'border-gray-200 text-gray-500 hover:border-indigo-200 hover:bg-gray-50'
            ]">
            <span class="text-2xl">{{ role.icon }}</span>
            {{ role.label }}
          </button>
        </div>

        <!-- Role description -->
        <div class="bg-indigo-50 rounded-xl px-4 py-3 mb-6 text-xs text-indigo-700 font-medium">
          {{ currentRole.desc }}
        </div>

        <!-- Login form -->
        <form @submit.prevent="handleLogin" class="space-y-4">
          <div v-if="error"
            class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg">
            {{ error }}
          </div>

          <div>
            <label class="form-label">Email Address</label>
            <input v-model="form.email" type="email" required autocomplete="email"
              :placeholder="currentRole.placeholder" class="input-field" />
          </div>

          <div>
            <label class="form-label">Password</label>
            <div class="relative">
              <input v-model="form.password" :type="showPw ? 'text' : 'password'" required
                autocomplete="current-password" placeholder="••••••••"
                class="input-field pr-10" />
              <button type="button" @click="showPw = !showPw"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path v-if="!showPw" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                  <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                </svg>
              </button>
            </div>
          </div>

          <button type="submit" :disabled="loading"
            :class="['w-full text-white font-semibold py-3 rounded-xl transition-colors flex items-center justify-center gap-2', currentRole.btnColor]">
            <svg v-if="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
            </svg>
            {{ loading ? 'Signing in...' : `Sign in as ${currentRole.label}` }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth   = useAuthStore()
const router = useRouter()
const loading      = ref(false)
const error        = ref('')
const showPw       = ref(false)
const selectedRole = ref('admin')
const form         = ref({ email: '', password: '' })

const roles = [
  {
    key:         'admin',
    label:       'Admin',
    icon:        '🛡️',
    desc:        'Full system access — manage students, instructors, courses, exams and view all reports.',
    placeholder: 'admin@exam.com',
    btnColor:    'bg-indigo-600 hover:bg-indigo-700',
  },
  {
    key:         'instructor',
    label:       'Instructor',
    icon:        '🎓',
    desc:        'Create and manage exams, add questions, and view student results.',
    placeholder: 'alice@exam.com',
    btnColor:    'bg-purple-600 hover:bg-purple-700',
  },
  {
    key:         'student',
    label:       'Student',
    icon:        '📚',
    desc:        'Take available exams and view your results and performance.',
    placeholder: 'charlie@exam.com',
    btnColor:    'bg-green-600 hover:bg-green-700',
  },
]

const currentRole = computed(() => roles.find(r => r.key === selectedRole.value))

function selectRole(role) {
  selectedRole.value  = role.key
  form.value.email    = ''
  form.value.password = ''
  error.value         = ''
}

async function handleLogin() {
  loading.value = true
  error.value   = ''
  try {
    await auth.login(form.value.email, form.value.password)
    router.push('/dashboard')
  } catch (e) {
    error.value = e.response?.data?.message
      || Object.values(e.response?.data?.errors || {})[0]?.[0]
      || 'Login failed. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>
