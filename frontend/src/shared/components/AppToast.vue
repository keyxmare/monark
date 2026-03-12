<script setup lang="ts">
import { computed } from 'vue'

import type { Toast } from '@/shared/stores/toast'

const props = defineProps<{
  toast: Toast
}>()

const emit = defineEmits<{
  close: [id: string]
}>()

const progressPercent = computed(() => {
  if (!props.toast.progress || props.toast.progress.total === 0) return 0
  return Math.round((props.toast.progress.current / props.toast.progress.total) * 100)
})

const variantClasses = computed(() => {
  switch (props.toast.variant) {
    case 'error': return 'border-red-500 bg-red-50 text-red-900'
    case 'info': return 'border-blue-500 bg-blue-50 text-blue-900'
    case 'progress': return 'border-primary bg-blue-50 text-blue-900'
    case 'success': return 'border-green-500 bg-green-50 text-green-900'
    default: return ''
  }
})

const progressBarColor = computed(() => {
  if (!props.toast.progress) return 'bg-primary'
  return progressPercent.value === 100 ? 'bg-green-500' : 'bg-primary'
})
</script>

<template>
  <div
    class="pointer-events-auto w-80 rounded-lg border-l-4 p-4 shadow-lg"
    :class="variantClasses"
    role="alert"
    :data-testid="`toast-${toast.id}`"
  >
    <div class="flex items-start justify-between gap-2">
      <div class="min-w-0 flex-1">
        <p class="text-sm font-semibold">
          {{ toast.title }}
        </p>
        <p
          v-if="toast.message"
          class="mt-1 text-xs opacity-80"
        >
          {{ toast.message }}
        </p>
      </div>
      <button
        class="shrink-0 text-current opacity-50 transition-opacity hover:opacity-100"
        :aria-label="'Close'"
        data-testid="toast-close"
        @click="emit('close', toast.id)"
      >
        <svg
          class="h-4 w-4"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          stroke-width="2"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M6 18L18 6M6 6l12 12"
          />
        </svg>
      </button>
    </div>

    <div
      v-if="toast.variant === 'progress' && toast.progress"
      class="mt-3"
    >
      <div class="mb-1 flex justify-between text-xs">
        <span>{{ toast.progress.current }}/{{ toast.progress.total }}</span>
        <span>{{ progressPercent }}%</span>
      </div>
      <div class="h-2 overflow-hidden rounded-full bg-black/10">
        <div
          class="h-full rounded-full transition-all duration-300"
          :class="progressBarColor"
          :style="{ width: `${progressPercent}%` }"
        />
      </div>
    </div>
  </div>
</template>
