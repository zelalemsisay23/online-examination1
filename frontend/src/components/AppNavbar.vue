<template>
  <header class="bg-white shadow-sm z-10 flex items-center justify-between px-6 py-3 flex-shrink-0">
    <button @click="$emit('toggle-sidebar')" class="text-gray-500 hover:text-indigo-600 focus:outline-none">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
      </svg>
    </button>

    <div class="relative" ref="menuRef">
      <button @click="menuOpen = !menuOpen"
        class="flex items-center gap-2 text-sm text-gray-700 hover:text-indigo-600 focus:outline-none">
        <div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-white font-semibold text-xs">
          {{ initials }}
        </div>
        <span class="hidden sm:block font-medium">{{ auth.user?.name }}</span>
        <span class="text-xs text-gray-400 capitalize hidden sm:block">({{ auth.user?.role }})</span>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
      </button>

      <transition name="fade">
        <div v-if="menuOpen"
          class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-100 py-1 z-50">
          <router-link to="/profile" @click="menuOpen = false"
            class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            Profile
          </router-link>
          <hr class="my-1 border-gray-100" />
          <button @click="handleLogout"
            class="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            Logout
          </button>
        </div>
      </transition>
    </div>
  </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

defineEmits(['toggle-sidebar'])
const auth    = useAuthStore()
const router  = useRouter()
const menuOpen = ref(false)
const menuRef  = ref(null)

const initials = computed(() =>
  (auth.user?.name || 'U').split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase()
)

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}

function handleOutsideClick(e) {
  if (menuRef.value && !menuRef.value.contains(e.target)) menuOpen.value = false
}
onMounted(()   => document.addEventListener('mousedown', handleOutsideClick))
onUnmounted(() => document.removeEventListener('mousedown', handleOutsideClick))
</script>
