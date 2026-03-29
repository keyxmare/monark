<script setup lang="ts">
import { onMounted } from 'vue';
import { useI18n } from 'vue-i18n';

import { useTechStackStore } from '@/catalog/stores/tech-stack';
import Pagination from '@/shared/components/Pagination.vue';

const PER_PAGE = 20;

const props = defineProps<{ projectId: string }>();

const { t } = useI18n();
const techStackStore = useTechStackStore();

onMounted(async () => {
  await techStackStore.fetchAll(1, PER_PAGE, props.projectId);
});

function changeTechStackPage(page: number) {
  techStackStore.fetchAll(page, PER_PAGE, props.projectId);
}
</script>

<template>
  <div data-testid="tech-stacks-panel">
    <div class="overflow-hidden rounded-xl border border-border bg-surface">
      <table class="w-full">
        <thead>
          <tr class="border-b border-border bg-surface-muted">
            <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
              {{ t('catalog.techStacks.language') }}
            </th>
            <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
              {{ t('catalog.techStacks.languageVersion') }}
            </th>
            <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
              {{ t('catalog.techStacks.framework') }}
            </th>
            <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
              {{ t('catalog.techStacks.frameworkVersion') }}
            </th>
            <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
              {{ t('catalog.techStacks.latestLts') }}
            </th>
            <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
              {{ t('catalog.techStacks.ltsGap') }}
            </th>
            <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
              {{ t('catalog.techStacks.syncedAt') }}
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="ts in techStackStore.techStacks"
            :key="ts.id"
            class="border-b border-border last:border-0"
            data-testid="tech-stack-row"
          >
            <td class="px-4 py-3 text-sm text-text">
              {{ ts.language }}
            </td>
            <td class="px-4 py-3 text-sm text-text-muted">
              {{ ts.version || '—' }}
            </td>
            <td class="px-4 py-3 text-sm text-text">
              {{ ts.framework }}
            </td>
            <td class="px-4 py-3 text-sm text-text-muted">
              <span class="inline-flex items-center gap-1.5">
                {{ ts.frameworkVersion || '—' }}
                <span
                  v-if="ts.maintenanceStatus === 'eol'"
                  class="rounded-full bg-danger/10 px-1.5 py-0.5 text-xs font-medium text-danger"
                  :title="
                    ts.eolDate
                      ? t('catalog.techStacks.unmaintainedSince', { date: ts.eolDate })
                      : t('catalog.techStacks.unmaintainedNoDate')
                  "
                  data-testid="tech-stack-eol-badge"
                >
                  {{ t('catalog.techStacks.unmaintained') }}
                </span>
                <span
                  v-else-if="ts.maintenanceStatus === 'warning'"
                  class="rounded-full bg-warning/10 px-1.5 py-0.5 text-xs font-medium text-warning"
                  data-testid="tech-stack-inactive-badge"
                >
                  {{ t('catalog.techStacks.inactive') }}
                </span>
              </span>
            </td>
            <td class="px-4 py-3 text-sm text-text-muted">
              {{ ts.latestLts ?? '—' }}
            </td>
            <td class="px-4 py-3 text-sm">
              <span
                v-if="ts.ltsGap"
                :class="{
                  'text-success': ts.maintenanceStatus === 'active',
                  'text-warning': ts.maintenanceStatus === 'warning',
                  'text-danger': ts.maintenanceStatus === 'eol',
                }"
              >
                {{ ts.ltsGap }}
              </span>
              <span v-else class="text-text-muted">—</span>
            </td>
            <td class="px-4 py-3 text-sm text-text-muted">
              {{ ts.versionSyncedAt ?? '—' }}
            </td>
          </tr>
        </tbody>
      </table>
      <div
        v-if="techStackStore.techStacks.length === 0"
        class="py-8 text-center text-text-muted"
        data-testid="tech-stacks-empty"
      >
        {{ t('catalog.projects.noTechStacks') }}
      </div>
    </div>
    <Pagination
      v-if="techStackStore.totalPages > 1"
      :page="techStackStore.currentPage"
      :total-pages="techStackStore.totalPages"
      data-testid="tech-stacks-pagination"
      @update:page="changeTechStackPage"
    />
  </div>
</template>
