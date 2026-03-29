<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';

import {
  humanizeTimeDiff,
  isVersionUpToDate,
  ltsUrgency,
  patchGap,
  useFrameworkLts,
} from '@/catalog/composables/useFrameworkLts';
import { useTechStackStore } from '@/catalog/stores/tech-stack';
import Pagination from '@/shared/components/Pagination.vue';

const PER_PAGE = 20;

const props = defineProps<{ projectId: string }>();

const { t } = useI18n();
const techStackStore = useTechStackStore();
const { getLtsInfo, getVersionMaintenanceStatus, getVersionReleaseDate, loadForFrameworks } =
  useFrameworkLts();

onMounted(async () => {
  await techStackStore.fetchAll(1, PER_PAGE, props.projectId);
  const frameworks = techStackStore.techStacks
    .map((ts) => ts.framework)
    .filter((f) => f && f !== 'none');
  await loadForFrameworks(frameworks);
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
              {{ t('catalog.techStacks.releasedAt') }}
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
                  v-if="
                    getVersionMaintenanceStatus(ts.framework, ts.frameworkVersion)?.status ===
                    'eol'
                  "
                  class="rounded-full bg-danger/10 px-1.5 py-0.5 text-xs font-medium text-danger"
                  :title="
                    getVersionMaintenanceStatus(ts.framework, ts.frameworkVersion)?.eolDate
                      ? t('catalog.techStacks.unmaintainedSince', {
                          date: getVersionMaintenanceStatus(
                            ts.framework,
                            ts.frameworkVersion,
                          )!.eolDate!,
                        })
                      : t('catalog.techStacks.unmaintainedNoDate')
                  "
                  data-testid="tech-stack-eol-badge"
                >
                  {{ t('catalog.techStacks.unmaintained') }}
                </span>
                <span
                  v-else-if="
                    getVersionMaintenanceStatus(ts.framework, ts.frameworkVersion)?.status ===
                    'warning'
                  "
                  class="rounded-full bg-warning/10 px-1.5 py-0.5 text-xs font-medium text-warning"
                  :title="
                    getVersionMaintenanceStatus(ts.framework, ts.frameworkVersion)
                      ?.lastRelease
                      ? t('catalog.techStacks.inactiveSince', {
                          duration: humanizeTimeDiff(
                            getVersionMaintenanceStatus(ts.framework, ts.frameworkVersion)!
                              .lastRelease!,
                            new Date().toISOString(),
                          ),
                        })
                      : t('catalog.techStacks.inactive')
                  "
                  data-testid="tech-stack-inactive-badge"
                >
                  {{ t('catalog.techStacks.inactive') }}
                </span>
              </span>
            </td>
            <td class="px-4 py-3 text-sm text-text-muted">
              {{ getLtsInfo(ts.framework)?.latestLts ?? '—' }}
            </td>
            <td class="px-4 py-3 text-sm">
              <template
                v-if="
                  getLtsInfo(ts.framework) &&
                  ts.frameworkVersion &&
                  getVersionReleaseDate(ts.framework, ts.frameworkVersion)
                "
              >
                <span
                  v-if="
                    isVersionUpToDate(
                      ts.frameworkVersion,
                      getLtsInfo(ts.framework)!.latestLts,
                    )
                  "
                  class="text-success"
                >
                  {{ t('catalog.techStacks.upToDate') }}
                </span>
                <span
                  v-else-if="
                    patchGap(ts.frameworkVersion, getLtsInfo(ts.framework)!.latestLts) !==
                    null
                  "
                  class="text-warning"
                >
                  {{
                    t('catalog.techStacks.patchesBehind', {
                      count: patchGap(
                        ts.frameworkVersion,
                        getLtsInfo(ts.framework)!.latestLts,
                      ),
                    })
                  }}
                </span>
                <span
                  v-else
                  :class="{
                    'text-success':
                      ltsUrgency(
                        getVersionReleaseDate(ts.framework, ts.frameworkVersion)!,
                        getLtsInfo(ts.framework)!.releaseDate,
                      ) === 'fresh',
                    'text-warning':
                      ltsUrgency(
                        getVersionReleaseDate(ts.framework, ts.frameworkVersion)!,
                        getLtsInfo(ts.framework)!.releaseDate,
                      ) === 'moderate',
                    'text-danger':
                      ltsUrgency(
                        getVersionReleaseDate(ts.framework, ts.frameworkVersion)!,
                        getLtsInfo(ts.framework)!.releaseDate,
                      ) === 'outdated',
                  }"
                >
                  {{
                    humanizeTimeDiff(
                      getVersionReleaseDate(ts.framework, ts.frameworkVersion)!,
                      getLtsInfo(ts.framework)!.releaseDate,
                    )
                  }}
                </span>
              </template>
              <span v-else class="text-text-muted">—</span>
            </td>
            <td class="px-4 py-3 text-sm text-text-muted">
              {{ getVersionReleaseDate(ts.framework, ts.frameworkVersion) ?? '—' }}
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
