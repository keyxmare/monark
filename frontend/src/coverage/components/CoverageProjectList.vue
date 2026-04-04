<script setup lang="ts">
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';

import type { CoverageProject } from '@/coverage/types';

const props = defineProps<{ projects: CoverageProject[] }>();

const router = useRouter();

type SortDir = 'asc' | 'desc';
type SortField = 'coverage' | 'name' | 'syncedAt';

const sortField = ref<SortField>('coverage');
const sortDir = ref<SortDir>('desc');

function sortIndicator(field: SortField): string {
  if (sortField.value !== field) return '';
  return sortDir.value === 'asc' ? ' ↑' : ' ↓';
}

function toggleSort(field: SortField) {
  if (sortField.value === field) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortField.value = field;
    sortDir.value = field === 'coverage' ? 'desc' : 'asc';
  }
}

const sorted = computed<CoverageProject[]>(() => {
  return [...props.projects].sort((a, b) => {
    let cmp = 0;
    if (sortField.value === 'coverage') {
      const av = a.coveragePercent ?? -1;
      const bv = b.coveragePercent ?? -1;
      cmp = av - bv;
    } else if (sortField.value === 'name') {
      cmp = a.projectName.localeCompare(b.projectName);
    } else if (sortField.value === 'syncedAt') {
      const ad = a.syncedAt ?? '';
      const bd = b.syncedAt ?? '';
      cmp = ad.localeCompare(bd);
    }
    return sortDir.value === 'asc' ? cmp : -cmp;
  });
});

function coverageBarClass(pct: null | number): string {
  if (pct === null) return 'bg-gray-300';
  if (pct >= 80) return 'bg-green-500';
  if (pct >= 60) return 'bg-orange-500';
  return 'bg-red-500';
}

function coverageTextClass(pct: null | number): string {
  if (pct === null) return 'text-text-muted';
  if (pct >= 80) return 'text-green-600';
  if (pct >= 60) return 'text-orange-500';
  return 'text-red-500';
}

function formatDate(iso: null | string): string {
  if (!iso) return '—';
  return new Date(iso).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
}

function navigate(slug: string) {
  router.push(`/coverage/${slug}`);
}

function truncate(hash: null | string, len = 7): string {
  if (!hash) return '—';
  return hash.slice(0, len);
}
</script>

<template>
  <div class="overflow-hidden rounded-xl border border-border bg-surface" data-testid="coverage-project-list">
    <table class="w-full">
      <thead>
        <tr class="border-b border-border bg-surface-muted">
          <th
            class="cursor-pointer px-4 py-3 text-left text-sm font-medium text-text-muted hover:text-text"
            @click="toggleSort('name')"
          >
            Projet{{ sortIndicator('name') }}
          </th>
          <th
            class="cursor-pointer px-4 py-3 text-left text-sm font-medium text-text-muted hover:text-text"
            @click="toggleSort('coverage')"
          >
            Coverage{{ sortIndicator('coverage') }}
          </th>
          <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">Tendance</th>
          <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">Source</th>
          <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">Commit</th>
          <th
            class="cursor-pointer px-4 py-3 text-left text-sm font-medium text-text-muted hover:text-text"
            @click="toggleSort('syncedAt')"
          >
            Date{{ sortIndicator('syncedAt') }}
          </th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="project in sorted"
          :key="project.projectId"
          class="cursor-pointer border-t border-border hover:bg-surface-muted/50"
          data-testid="coverage-project-row"
          @click="navigate(project.projectSlug)"
        >
          <td class="px-4 py-3 text-sm font-medium text-text">{{ project.projectName }}</td>
          <td class="px-4 py-3">
            <div v-if="project.coveragePercent !== null" class="flex items-center gap-2">
              <div class="h-2 w-24 overflow-hidden rounded-full bg-surface-muted">
                <div
                  :class="coverageBarClass(project.coveragePercent)"
                  :style="{ width: `${project.coveragePercent}%` }"
                  class="h-full rounded-full transition-all"
                  data-testid="coverage-bar"
                />
              </div>
              <span :class="coverageTextClass(project.coveragePercent)" class="text-sm font-medium">
                {{ project.coveragePercent }}%
              </span>
            </div>
            <div v-else class="flex items-center gap-2">
              <div class="h-2 w-24 overflow-hidden rounded-full bg-surface-muted">
                <div class="h-full w-0 rounded-full bg-gray-300" data-testid="coverage-bar" />
              </div>
              <span class="text-sm font-medium text-text-muted">0%</span>
            </div>
          </td>
          <td class="px-4 py-3 text-sm">
            <span v-if="project.trend !== null && project.trend > 0" class="text-green-500">↑ +{{ project.trend }}</span>
            <span v-else-if="project.trend !== null && project.trend < 0" class="text-red-500">↓ {{ project.trend }}</span>
            <span v-else class="text-text-muted">—</span>
          </td>
          <td class="px-4 py-3 text-sm text-text-muted">{{ project.source ?? '—' }}</td>
          <td class="px-4 py-3 font-mono text-xs text-text-muted" data-testid="coverage-commit">
            {{ truncate(project.commitHash) }}
          </td>
          <td class="px-4 py-3 text-sm text-text-muted">{{ formatDate(project.syncedAt) }}</td>
        </tr>
      </tbody>
    </table>

    <div
      v-if="projects.length === 0"
      class="flex flex-col items-center py-12"
      data-testid="coverage-empty"
    >
      <p class="text-sm font-medium text-text">Aucun projet avec coverage</p>
      <p class="text-sm text-text-muted">Lancez une synchronisation pour récupérer les données.</p>
    </div>
  </div>
</template>
