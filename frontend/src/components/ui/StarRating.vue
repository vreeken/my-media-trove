<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  rating: number
  readonly?: boolean
}>()

const emit = defineEmits<{
  'update:rating': [value: number]
}>()

const stars = computed(() => {
  return Array.from({ length: 10 }, (_, i) => ({
    value: i + 1,
    filled: i < props.rating,
  }))
})

function setRating(value: number) {
  if (props.readonly) return
  emit('update:rating', value)
}
</script>

<template>
  <div class="flex gap-1" :class="{ 'cursor-pointer': !readonly }">
    <button
      v-for="star in stars"
      :key="star.value"
      @click="setRating(star.value)"
      :disabled="readonly"
      class="w-6 h-6 transition-colors disabled:cursor-default"
      :class="star.filled ? 'text-yellow-400' : 'text-dark-600 hover:text-dark-500'"
    >
      <svg fill="currentColor" viewBox="0 0 20 20">
        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
      </svg>
    </button>
    <span v-if="rating > 0" class="ml-2 text-sm text-dark-300">{{ rating }}/10</span>
  </div>
</template>
