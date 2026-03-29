<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { RouterLink } from 'vue-router';

import type { GroupBy, GroupedStack, SortField } from '@/catalog/composables/useTechStackGrouping';

import {
  humanizeTimeDiff,
  isVersionUpToDate,
  ltsUrgency,
  patchGap,
  useFrameworkLts,
} from '@/catalog/composables/useFrameworkLts';
import TechBadge from '@/shared/components/TechBadge.vue';

const { t } = useI18n();
const { getLtsInfo, getVersionMaintenanceStatus, getVersionReleaseDate } = useFrameworkLts();

const props = defineProps<{
  groupBy: GroupBy;
  groupedStacks: GroupedStack[];
  sortIndicator: (field: SortField) => string;
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
            {{ t('catalog.techStacks.releasedAt') }}
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
                v-if="
                  getVersionMaintenanceStatus(row.ts.framework, row.ts.frameworkVersion)?.status ===
                  'eol'
                "
                class="rounded-full bg-danger/10 px-1.5 py-0.5 text-xs font-medium text-danger"
                :title="
                  getVersionMaintenanceStatus(row.ts.framework, row.ts.frameworkVersion)?.eolDate
                    ? t('catalog.techStacks.unmaintainedSince', {
                        date: getVersionMaintenanceStatus(
                          row.ts.framework,
                          row.ts.frameworkVersion,
                        )!.eolDate!,
                      })
                    : t('catalog.techStacks.unmaintainedNoDate')
                "
              >
                {{ t('catalog.techStacks.unmaintained') }}
              </span>
              <span
                v-else-if="
                  getVersionMaintenanceStatus(row.ts.framework, row.ts.frameworkVersion)?.status ===
                  'warning'
                "
                class="rounded-full bg-warning/10 px-1.5 py-0.5 text-xs font-medium text-warning"
                :title="
                  getVersionMaintenanceStatus(row.ts.framework, row.ts.frameworkVersion)
                    ?.lastRelease
                    ? t('catalog.techStacks.inactiveSince', {
                        duration: humanizeTimeDiff(
                          getVersionMaintenanceStatus(row.ts.framework, row.ts.frameworkVersion)!
                            .lastRelease!,
                          new Date().toISOString(),
                        ),
                      })
                    : t('catalog.techStacks.inactive')
                "
              >
                {{ t('catalog.techStacks.inactive') }}
              </span>
            </span>
          </td>
          <td class="px-4 py-3 text-sm text-text-muted">
            {{ getLtsInfo(row.ts.framework)?.latestLts ?? '—' }}
          </td>
          <td class="px-4 py-3 text-sm">
            <template
              v-if="
                getLtsInfo(row.ts.framework) &&
                row.ts.frameworkVersion &&
                getVersionReleaseDate(row.ts.framework, row.ts.frameworkVersion)
              "
            >
              <span
                v-if="
                  isVersionUpToDate(
                    row.ts.frameworkVersion,
                    getLtsInfo(row.ts.framework)!.latestLts,
                  )
                "
                class="text-success"
              >
                {{ t('catalog.techStacks.upToDate') }}
              </span>
              <span
                v-else-if="
                  patchGap(row.ts.frameworkVersion, getLtsInfo(row.ts.framework)!.latestLts) !==
                  null
                "
                class="text-warning"
              >
                {{
                  t('catalog.techStacks.patchesBehind', {
                    count: patchGap(
                      row.ts.frameworkVersion,
                      getLtsInfo(row.ts.framework)!.latestLts,
                    ),
                  })
                }}
              </span>
              <span
                v-else
                :class="{
                  'text-success':
                    ltsUrgency(
                      getVersionReleaseDate(row.ts.framework, row.ts.frameworkVersion)!,
                      getLtsInfo(row.ts.framework)!.releaseDate,
                    ) === 'fresh',
                  'text-warning':
                    ltsUrgency(
                      getVersionReleaseDate(row.ts.framework, row.ts.frameworkVersion)!,
                      getLtsInfo(row.ts.framework)!.releaseDate,
                    ) === 'moderate',
                  'text-danger':
                    ltsUrgency(
                      getVersionReleaseDate(row.ts.framework, row.ts.frameworkVersion)!,
                      getLtsInfo(row.ts.framework)!.releaseDate,
                    ) === 'outdated',
                }"
              >
                {{
                  humanizeTimeDiff(
                    getVersionReleaseDate(row.ts.framework, row.ts.frameworkVersion)!,
                    getLtsInfo(row.ts.framework)!.releaseDate,
                  )
                }}
              </span>
            </template>
            <span v-else class="text-text-muted">—</span>
          </td>
          <td class="px-4 py-3 text-sm text-text-muted">
            {{ getVersionReleaseDate(row.ts.framework, row.ts.frameworkVersion) ?? '—' }}
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
