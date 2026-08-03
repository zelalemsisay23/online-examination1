<template>
  <teleport to="body">
    <transition name="fade">
      <div v-if="modelValue"
        class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
        @mousedown.self="$emit('update:modelValue', false)">
        <div :class="['bg-white rounded-2xl shadow-xl w-full', sizeClass]" @mousedown.stop>
          <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-800">{{ title }}</h3>
            <button @click="$emit('update:modelValue', false)"
              class="text-gray-400 hover:text-gray-600 transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>
          <div class="px-6 py-4"><slot /></div>
          <div v-if="$slots.footer" class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title:      { type: String,  default: '' },
  size:       { type: String,  default: 'md' },
})

defineEmits(['update:modelValue'])

const sizeClass = computed(() => ({
  sm: 'max-w-sm', md: 'max-w-lg', lg: 'max-w-2xl', xl: 'max-w-4xl',
}[props.size] || 'max-w-lg'))
</script>
