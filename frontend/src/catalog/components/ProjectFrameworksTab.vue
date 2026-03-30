<script setup lang="ts">
import { onMounted } from 'vue';
import { useI18n } from 'vue-i18n';

import { useFrameworkStore } from '@/catalog/stores/framework';
import Pagination from '@/shared/components/Pagination.vue';

const PER_PAGE = 20;
const props = defineProps<{ projectId: string }>();
const { t } = useI18n();
const frameworkStore = useFrameworkStore();

onMounted(async () => {
  await frameworkStore.fetchAll(1, PER_PAGE, props.projectId);
});

function changePage(page: number) {
  frameworkStore.fetchAll(page, PER_PAGE, props.projectId);
}
</script>

<template>
  <div data-testid="frameworks-panel">
    <div class="overflow-hidden rounded-xl border border-border bg-surface">
      <table class="w-full">
        <thead>
          <tr class="border-b border-border bg-surface-muted">
            <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">{{ t('catalog.frameworks.framework') }}</th>
            <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">{{ t('catalog.frameworks.version') }}</th>
            <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">{{ t('catalog.techStacks.latestLts') }}</th>
            <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">{{ t('catalog.techStacks.ltsGap') }}</th>
            <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">{{ t('catalog.techStacks.syncedAt') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="fw in frameworkStore.frameworks" :key="fw.id" class="border-b border-border last:border-0" data-testid="framework-row">
            <td class="px-4 py-3 text-sm text-text">{{ fw.name }}</td>
            <td class="px-4 py-3 text-sm text-text-muted">
              <span class="inline-flex items-center gap-1.5">
                {{ fw.version || '—' }}
                <span v-if="fw.maintenanceStatus === 'eol'" class="rounded-full bg-danger/10 px-1.5 py-0.5 text-xs font-medium text-danger" :title="fw.eolDate ? t('catalog.techStacks.unmaintainedSince', { date: fw.eolDate }) : t('catalog.techStacks.unmaintainedNoDate')">{{ t('catalog.techStacks.unmaintained') }}</span>
                <span v-else-if="fw.maintenanceStatus === 'warning'" class="rounded-full bg-warning/10 px-1.5 py-0.5 text-xs font-medium text-warning">{{ t('catalog.techStacks.inactive') }}</span>
              </span>
            </td>
            <td class="px-4 py-3 text-sm text-text-muted">{{ fw.latestLts ?? '—' }}</td>
            <td class="px-4 py-3 text-sm">
              <span v-if="fw.ltsGap" :class="{ 'text-success': fw.maintenanceStatus === 'active', 'text-warning': fw.maintenanceStatus === 'warning', 'text-danger': fw.maintenanceStatus === 'eol' }">{{ fw.ltsGap }}</span>
              <span v-else class="text-text-muted">—</span>
            </td>
            <td class="px-4 py-3 text-sm text-text-muted">{{ fw.versionSyncedAt ?? '—' }}</td>
          </tr>
        </tbody>
      </table>
      <div v-if="frameworkStore.frameworks.length === 0" class="py-8 text-center text-text-muted" data-testid="frameworks-empty">
        {{ t('catalog.frameworks.noFrameworks') }}
      </div>
    </div>
    <Pagination v-if="frameworkStore.totalPages > 1" :page="frameworkStore.currentPage" :total-pages="frameworkStore.totalPages" data-testid="frameworks-pagination" @update:page="changePage" />
  </div>
</template>
