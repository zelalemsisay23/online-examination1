<template>
  <div class="bg-white rounded-xl shadow-sm p-6 flex items-center gap-4 border border-gray-100">
    <div :class="`w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0 ${colorClass}`">
      <slot name="icon" />
    </div>
    <div>
      <p class="text-sm text-gray-500 font-medium">{{ label }}</p>
      <p class="text-2xl font-bold text-gray-800">{{ value }}</p>
      <p v-if="sub" class="text-xs text-gray-400 mt-0.5">{{ sub }}</p>
    </div>
  </div>
</template>

<script setup>
defineProps({
  label:      { type: String, required: true },
  value:      { type: [String, Number], required: true },
  sub:        { type: String, default: '' },
  colorClass: { type: String, default: 'bg-indigo-100 text-indigo-600' },
})
</script>
