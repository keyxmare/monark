<script setup lang="ts">
import { computed, ref, watch } from 'vue';

import { useGlobalSync } from '@/shared/composables/useGlobalSync';
import { STEP_LABELS, STEP_ORDER, type SyncStepName } from '@/shared/types/globalSync';

const { currentSync } = useGlobalSync();

const visible = ref(false);
const fadingOut = ref(false);

watch(
  currentSync,
  (val) => {
    if (val) {
      fadingOut.value = false;
      visible.value = true;
    } else {
      fadingOut.value = true;
      setTimeout(() => {
        visible.value = false;
        fadingOut.value = false;
      }, 500);
    }
  },
  { immediate: true },
);

const progressPercent = computed(() => {
  if (!currentSync.value) return 0;
  const { stepProgress, stepTotal } = currentSync.value;
  if (!stepTotal) return 0;
  return Math.round((stepProgress / stepTotal) * 100);
});

function stepState(stepName: SyncStepName): 'active' | 'completed' | 'pending' {
  if (!currentSync.value) return 'pending';
  const { completedSteps, currentStepName, status } = currentSync.value;
  if (completedSteps.includes(stepName)) return 'completed';
  if (status === 'completed') return 'completed';
  if (currentStepName === stepName) return 'active';
  return 'pending';
}
</script>

<template>
  <Transition name="banner">
    <div
      v-if="visible"
      :class="[
        'border-b border-primary/20 bg-primary/5 px-6 py-3',
        fadingOut ? 'opacity-0' : 'opacity-100',
        'transition-opacity duration-500',
      ]"
      data-testid="sync-progress-banner"
      role="status"
      aria-live="polite"
    >
      <div class="flex flex-col gap-2">
        <div class="flex items-center gap-4">
          <div
            v-for="(stepName, index) in STEP_ORDER"
            :key="stepName"
            class="flex items-center gap-2"
          >
            <div class="flex items-center gap-1.5">
              <span
                v-if="stepState(stepName) === 'completed'"
                class="flex h-5 w-5 items-center justify-center rounded-full bg-success text-white text-xs font-bold"
                data-testid="step-completed"
              >
                ✓
              </span>
              <span
                v-else-if="stepState(stepName) === 'active'"
                class="flex h-5 w-5 items-center justify-center rounded-full bg-primary text-white text-xs"
                data-testid="step-active"
              >
                ●
              </span>
              <span
                v-else
                class="flex h-5 w-5 items-center justify-center rounded-full border-2 border-border text-text-muted text-xs"
                data-testid="step-pending"
              >
                ○
              </span>
              <span
                :class="{
                  'font-medium text-success': stepState(stepName) === 'completed',
                  'font-medium text-primary': stepState(stepName) === 'active',
                  'text-text-muted': stepState(stepName) === 'pending',
                }"
                class="text-sm"
              >
                {{ STEP_LABELS[stepName] }}
                <span
                  v-if="stepState(stepName) === 'active' && currentSync?.stepTotal"
                  class="text-xs font-normal"
                >
                  ({{ currentSync.stepProgress }}/{{ currentSync.stepTotal }})
                </span>
              </span>
            </div>
            <span v-if="index < STEP_ORDER.length - 1" class="text-border">────</span>
          </div>
        </div>

        <div v-if="currentSync?.status === 'running'" class="flex items-center gap-3">
          <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-border">
            <div
              class="h-full rounded-full bg-primary transition-all duration-300"
              :style="{ width: `${progressPercent}%` }"
              data-testid="sync-progress-bar"
            />
          </div>
          <span
            v-if="currentSync.message"
            class="text-xs text-text-muted"
            data-testid="sync-message"
          >
            {{ currentSync.message }}
          </span>
        </div>
      </div>
    </div>
  </Transition>
</template>
