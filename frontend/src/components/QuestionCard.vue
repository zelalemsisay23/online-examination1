<template>
  <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
    <div class="flex items-start justify-between gap-4 mb-4">
      <div class="flex items-start gap-3">
        <span class="flex-shrink-0 w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 text-sm font-bold flex items-center justify-center">
          {{ number }}
        </span>
        <p class="text-gray-800 font-medium leading-relaxed">{{ question.question_text }}</p>
      </div>
      <span class="flex-shrink-0 text-xs font-semibold text-indigo-600 bg-indigo-50 px-2 py-1 rounded-full whitespace-nowrap">
        {{ question.marks }} mark{{ question.marks !== 1 ? 's' : '' }}
      </span>
    </div>

    <div v-if="question.question_type !== 'short_answer'" class="space-y-2 ml-10">
      <label v-for="(option, idx) in question.options" :key="idx"
        class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-colors"
        :class="modelValue === option
          ? 'border-indigo-400 bg-indigo-50'
          : 'border-gray-200 hover:border-indigo-200 hover:bg-gray-50'">
        <input type="radio" :name="`q${question.id}`" :value="option"
          :checked="modelValue === option"
          @change="$emit('update:modelValue', option)"
          class="accent-indigo-600" />
        <span class="text-sm text-gray-700">{{ option }}</span>
      </label>
    </div>

    <div v-else class="ml-10">
      <textarea rows="3" :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        placeholder="Type your answer here..."
        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700
               focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent resize-none" />
    </div>
  </div>
</template>

<script setup>
defineProps({
  question:   { type: Object, required: true },
  number:     { type: Number, required: true },
  modelValue: { type: String, default: '' },
})
defineEmits(['update:modelValue'])
</script>
