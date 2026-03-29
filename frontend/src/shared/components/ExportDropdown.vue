<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const emit = defineEmits<{
  export: [format: 'csv' | 'pdf'];
}>();

const { t } = useI18n();
const open = ref(false);

function closeDelayed() {
  window.setTimeout(() => {
    open.value = false;
  }, 150);
}

function select(format: 'csv' | 'pdf') {
  open.value = false;
  emit('export', format);
}
</script>

<template>
  <div class="relative">
    <button
      class="inline-flex items-center gap-1.5 rounded-lg border border-border bg-surface px-4 py-2 text-sm font-medium text-text transition-colors hover:bg-surface-muted"
      data-testid="export-dropdown-trigger"
      @click="open = !open"
      @blur="closeDelayed"
    >
      {{ t('common.actions.export') }}
      <svg
        class="h-3.5 w-3.5"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M19.5 8.25l-7.5 7.5-7.5-7.5"
        />
      </svg>
    </button>
    <div
      v-if="open"
      class="absolute right-0 z-10 mt-1 w-36 rounded-lg border border-border bg-surface py-1 shadow-lg"
      data-testid="export-dropdown-menu"
    >
      <button
        class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-text hover:bg-surface-muted"
        data-testid="export-csv"
        @mousedown.prevent="select('csv')"
      >
        <svg
          class="h-4 w-4 text-text-muted"
          fill="none"
          stroke="currentColor"
          stroke-width="1.5"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 01-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-7.5A1.125 1.125 0 0112 18.375m9.75-12.75c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125m19.5 0v1.5c0 .621-.504 1.125-1.125 1.125M2.25 5.625v1.5c0 .621.504 1.125 1.125 1.125m0 0h17.25m-17.25 0h7.5c.621 0 1.125.504 1.125 1.125M3.375 8.25c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m17.25-3.75h-7.5c-.621 0-1.125.504-1.125 1.125m8.625-1.125c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125M12 10.875v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 10.875c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125M13.125 12h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125M20.625 12c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5M12 14.625v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 14.625c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125m0 0v1.5c0 .621-.504 1.125-1.125 1.125"
          />
        </svg>
        CSV
      </button>
      <button
        class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-text hover:bg-surface-muted"
        data-testid="export-pdf"
        @mousedown.prevent="select('pdf')"
      >
        <svg
          class="h-4 w-4 text-text-muted"
          fill="none"
          stroke="currentColor"
          stroke-width="1.5"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H6.75a2.25 2.25 0 00-2.25 2.25v13.5a2.25 2.25 0 002.25 2.25h10.5a2.25 2.25 0 002.25-2.25v-2.25"
          />
        </svg>
        PDF
      </button>
    </div>
  </div>
</template>
