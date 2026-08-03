<template>
  <div :class="[
    'flex items-center gap-2 px-4 py-2 rounded-xl font-mono font-bold text-lg',
    urgent ? 'bg-red-100 text-red-600 animate-pulse' : 'bg-indigo-100 text-indigo-700'
  ]">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    {{ formatted }}
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({ seconds: { type: Number, required: true } })
const emit  = defineEmits(['expired'])

const remaining = ref(props.seconds)
let timer = null

const formatted = computed(() => {
  const h = Math.floor(remaining.value / 3600)
  const m = Math.floor((remaining.value % 3600) / 60)
  const s = remaining.value % 60
  if (h > 0) return `${h}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`
  return `${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`
})

const urgent = computed(() => remaining.value <= 60 && remaining.value > 0)

onMounted(() => {
  timer = setInterval(() => {
    if (remaining.value <= 0) { clearInterval(timer); emit('expired'); return }
    remaining.value--
  }, 1000)
})
onUnmounted(() => clearInterval(timer))
</script>
