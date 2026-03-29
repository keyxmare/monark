<script setup lang="ts">
import { useI18n } from 'vue-i18n';

withDefaults(
  defineProps<{
    page: number;
    total?: number;
    totalPages: number;
  }>(),
  {
    total: undefined,
  },
);

defineEmits<{
  'update:page': [page: number];
}>();

const { t } = useI18n();
</script>

<template>
  <div
    class="mt-4 flex items-center justify-center gap-2"
    data-testid="pagination"
  >
    <button
      :disabled="page <= 1"
      class="rounded-lg border border-border px-3 py-1.5 text-sm text-text transition-colors hover:bg-background disabled:opacity-50"
      data-testid="pagination-prev"
      @click="$emit('update:page', page - 1)"
    >
      {{ t('common.pagination.previous') }}
    </button>
    <span
      class="text-sm text-text-muted"
      data-testid="pagination-info"
    >
      {{ t('common.pagination.page', { current: page, total: totalPages }) }}
    </span>
    <span
      v-if="total !== undefined"
      class="text-sm text-text-muted"
      data-testid="pagination-total"
    >
      ({{ total }} {{ t('common.pagination.items') }})
    </span>
    <button
      :disabled="page >= totalPages"
      class="rounded-lg border border-border px-3 py-1.5 text-sm text-text transition-colors hover:bg-background disabled:opacity-50"
      data-testid="pagination-next"
      @click="$emit('update:page', page + 1)"
    >
      {{ t('common.pagination.next') }}
    </button>
  </div>
</template>
