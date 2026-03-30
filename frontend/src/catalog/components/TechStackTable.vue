<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { RouterLink } from 'vue-router';

import type {
  GroupBy,
  GroupedStack,
  SortField,
  ViewMode,
} from '@/catalog/composables/useTechStackGrouping';

import TechBadge from '@/shared/components/TechBadge.vue';
import { formatRelative } from '@/shared/utils/dateFormat';

const { t } = useI18n();

const props = defineProps<{
  groupBy: GroupBy;
  groupedStacks: GroupedStack[];
  sortIndicator: (field: SortField) => string;
  viewMode: ViewMode;
}>();

const emit = defineEmits<{
  sort: [field: SortField];
}>();
</script>

<template>
  <div class="overflow-hidden rounded-xl border border-border bg-surface">
    <table class="w-full" data-testid="tech-stack-list-table">
      <thead>
        <tr class="border-b border-border bg-surface-muted">
          <th
            class="cursor-pointer px-4 py-3 text-left text-sm font-medium text-text-muted hover:text-text"
            @click="emit('sort', 'project')"
          >
            {{ t('catalog.techStacks.project') }}{{ props.sortIndicator('project') }}
          </th>
          <!-- Language mode columns -->
          <template v-if="props.viewMode === 'languages'">
            <th
              class="cursor-pointer px-4 py-3 text-left text-sm font-medium text-text-muted hover:text-text"
              @click="emit('sort', 'language')"
            >
              {{ t('catalog.techStacks.language') }}{{ props.sortIndicator('language') }}
            </th>
            <th
              class="cursor-pointer px-4 py-3 text-left text-sm font-medium text-text-muted hover:text-text"
              @click="emit('sort', 'languageVersion')"
            >
              {{ t('catalog.techStacks.version') }}{{ props.sortIndicator('languageVersion') }}
            </th>
          </template>
          <!-- Framework mode columns -->
          <template v-else>
            <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
              {{ t('catalog.techStacks.language') }}
            </th>
            <th
              class="cursor-pointer px-4 py-3 text-left text-sm font-medium text-text-muted hover:text-text"
              @click="emit('sort', 'framework')"
            >
              {{ t('catalog.techStacks.framework') }}{{ props.sortIndicator('framework') }}
            </th>
            <th
              class="cursor-pointer px-4 py-3 text-left text-sm font-medium text-text-muted hover:text-text"
              @click="emit('sort', 'frameworkVersion')"
            >
              {{ t('catalog.techStacks.frameworkVersion')
              }}{{ props.sortIndicator('frameworkVersion') }}
            </th>
          </template>
          <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
            {{ t('catalog.techStacks.latestLts') }}
          </th>
          <th
            class="cursor-pointer px-4 py-3 text-left text-sm font-medium text-text-muted hover:text-text"
            @click="emit('sort', 'ltsGap')"
          >
            {{ t('catalog.techStacks.ltsGap') }}{{ props.sortIndicator('ltsGap') }}
          </th>
          <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
            {{ t('catalog.techStacks.syncedAt') }}
          </th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="row in groupedStacks"
          :key="row.ts.id"
          :class="[
            row.isFirstInGroup ? 'border-t border-border first:border-0' : '',
            row.groupIndex % 2 === 1 ? 'bg-surface-muted/50' : '',
          ]"
          data-testid="tech-stack-list-row"
        >
          <td
            v-if="row.isFirstInGroup"
            :rowspan="row.groupSize"
            class="px-4 py-3 text-sm align-top"
          >
            <RouterLink
              v-if="props.groupBy === 'project'"
              :to="{ name: 'catalog-projects-detail', params: { id: row.projectId } }"
              class="font-medium text-primary hover:text-primary-dark"
            >
              {{ row.projectName }}
            </RouterLink>
            <span v-else class="font-medium text-text">{{ row.projectName }}</span>
          </td>
          <!-- Language mode cells -->
          <template v-if="props.viewMode === 'languages'">
            <td class="px-4 py-3 text-sm text-text">
              <TechBadge :name="row.ts.language" :version="row.ts.version" size="sm" />
            </td>
            <td class="px-4 py-3 text-sm text-text-muted">
              <span class="inline-flex items-center gap-1.5">
                {{ row.ts.version || '—' }}
                <span
                  v-if="row.ts.maintenanceStatus === 'eol'"
                  class="rounded-full bg-danger/10 px-1.5 py-0.5 text-xs font-medium text-danger"
                >
                  {{ t('catalog.techStacks.unmaintained') }}
                </span>
                <span
                  v-else-if="row.ts.maintenanceStatus === 'warning'"
                  class="rounded-full bg-warning/10 px-1.5 py-0.5 text-xs font-medium text-warning"
                >
                  {{ t('catalog.techStacks.inactive') }}
                </span>
              </span>
            </td>
          </template>
          <!-- Framework mode cells -->
          <template v-else>
            <td class="px-4 py-3 text-sm text-text">
              {{ row.ts.language }}
            </td>
            <td class="px-4 py-3 text-sm text-text">
              <TechBadge :name="row.ts.framework" :version="row.ts.frameworkVersion" size="sm" />
            </td>
            <td class="px-4 py-3 text-sm text-text-muted">
              <span class="inline-flex items-center gap-1.5">
                {{ row.ts.frameworkVersion || '—' }}
                <span
                  v-if="row.ts.maintenanceStatus === 'eol'"
                  class="rounded-full bg-danger/10 px-1.5 py-0.5 text-xs font-medium text-danger"
                  :title="
                    row.ts.eolDate
                      ? t('catalog.techStacks.unmaintainedSince', { date: row.ts.eolDate })
                      : t('catalog.techStacks.unmaintainedNoDate')
                  "
                >
                  {{ t('catalog.techStacks.unmaintained') }}
                </span>
                <span
                  v-else-if="row.ts.maintenanceStatus === 'warning'"
                  class="rounded-full bg-warning/10 px-1.5 py-0.5 text-xs font-medium text-warning"
                >
                  {{ t('catalog.techStacks.inactive') }}
                </span>
              </span>
            </td>
          </template>
          <!-- Shared LTS columns -->
          <td class="px-4 py-3 text-sm text-text-muted">
            {{ row.ts.latestLts ?? '—' }}
          </td>
          <td class="px-4 py-3 text-sm">
            <span
              v-if="row.ts.ltsGap"
              :class="{
                'text-success': row.ts.maintenanceStatus === 'active',
                'text-warning': row.ts.maintenanceStatus === 'warning',
                'text-danger': row.ts.maintenanceStatus === 'eol',
              }"
            >
              {{ row.ts.ltsGap }}
            </span>
            <span v-else class="text-text-muted">—</span>
          </td>
          <td class="px-4 py-3 text-sm text-text-muted">
            {{ row.ts.versionSyncedAt ? formatRelative(row.ts.versionSyncedAt) : '—' }}
          </td>
        </tr>
      </tbody>
    </table>

    <div
      v-if="groupedStacks.length === 0"
      class="py-8 text-center text-text-muted"
      data-testid="tech-stack-list-no-match"
    >
      {{ t('catalog.techStacks.noMatchingStacks') }}
    </div>
  </div>
</template>
