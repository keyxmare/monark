<script setup lang="ts">
import { onMounted } from 'vue';
import { useI18n } from 'vue-i18n';

import { useLanguageStore } from '@/catalog/stores/language';
import Pagination from '@/shared/components/Pagination.vue';

const PER_PAGE = 20;
const props = defineProps<{ projectId: string }>();
const { t } = useI18n();
const languageStore = useLanguageStore();

onMounted(async () => {
  await languageStore.fetchAll(1, PER_PAGE, props.projectId);
});

function changePage(page: number) {
  languageStore.fetchAll(page, PER_PAGE, props.projectId);
}
</script>

<template>
  <div data-testid="languages-panel">
    <div class="overflow-hidden rounded-xl border border-border bg-surface">
      <table class="w-full">
        <thead>
          <tr class="border-b border-border bg-surface-muted">
            <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
              {{ t('catalog.languages.language') }}
            </th>
            <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
              {{ t('catalog.languages.version') }}
            </th>
            <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
              {{ t('catalog.languages.eolDate') }}
            </th>
            <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
              {{ t('catalog.languages.status') }}
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="lang in languageStore.languages"
            :key="lang.id"
            class="border-b border-border last:border-0"
            data-testid="language-row"
          >
            <td class="px-4 py-3 text-sm text-text">{{ lang.name }}</td>
            <td class="px-4 py-3 text-sm text-text-muted">{{ lang.version || '—' }}</td>
            <td class="px-4 py-3 text-sm text-text-muted">{{ lang.eolDate ?? '—' }}</td>
            <td class="px-4 py-3 text-sm">
              <span
                v-if="lang.maintenanceStatus === 'eol'"
                class="rounded-full bg-danger/10 px-2 py-0.5 text-xs font-medium text-danger"
                >{{ t('catalog.techStacks.unmaintained') }}</span
              >
              <span
                v-else-if="lang.maintenanceStatus === 'active'"
                class="rounded-full bg-success/10 px-2 py-0.5 text-xs font-medium text-success"
                >{{ t('catalog.techStacks.statusActive') }}</span
              >
              <span v-else class="text-text-muted">—</span>
            </td>
          </tr>
        </tbody>
      </table>
      <div
        v-if="languageStore.languages.length === 0"
        class="py-8 text-center text-text-muted"
        data-testid="languages-empty"
      >
        {{ t('catalog.languages.noLanguages') }}
      </div>
    </div>
    <Pagination
      v-if="languageStore.totalPages > 1"
      :page="languageStore.currentPage"
      :total-pages="languageStore.totalPages"
      data-testid="languages-pagination"
      @update:page="changePage"
    />
  </div>
</template>
