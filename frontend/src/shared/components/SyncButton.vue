<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

import { useGlobalSync } from '@/shared/composables/useGlobalSync';

const { t } = useI18n();
const { currentSync, isRunning, startSync } = useGlobalSync();

const loading = ref(false);

async function handleClick() {
  if (isRunning.value || loading.value) return;
  loading.value = true;
  try {
    await startSync();
  } catch (err: unknown) {
    if (err instanceof Error && err.message !== 'sync_already_running') {
      console.error(err);
    }
  } finally {
    loading.value = false;
  }
}

function stepLabel(): string {
  if (!currentSync.value) return '';
  return `Step ${currentSync.value.currentStep}/3`;
}
</script>

<template>
  <button
    :disabled="isRunning || loading"
    class="flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark disabled:cursor-not-allowed disabled:opacity-60"
    data-testid="sync-button"
    @click="handleClick"
  >
    <svg
      v-if="isRunning || loading"
      class="h-4 w-4 animate-spin"
      fill="none"
      viewBox="0 0 24 24"
      aria-hidden="true"
    >
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
      <path
        class="opacity-75"
        fill="currentColor"
        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
      />
    </svg>
    <svg
      v-else
      class="h-4 w-4"
      fill="none"
      stroke="currentColor"
      stroke-width="1.5"
      viewBox="0 0 24 24"
      aria-hidden="true"
    >
      <path
        stroke-linecap="round"
        stroke-linejoin="round"
        d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"
      />
    </svg>
    <span>
      <template v-if="isRunning">Sync en cours — {{ stepLabel() }}</template>
      <template v-else>{{ t('sync.button.label', 'Synchroniser') }}</template>
    </span>
  </button>
</template>
