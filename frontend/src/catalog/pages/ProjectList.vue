<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink, useRouter } from 'vue-router';

import type { Project } from '@/catalog/types/project';

import { useProjectStore } from '@/catalog/stores/project';
import ConfirmDialog from '@/shared/components/ConfirmDialog.vue';
import DropdownMenu from '@/shared/components/DropdownMenu.vue';
import Pagination from '@/shared/components/Pagination.vue';
import SyncButton from '@/shared/components/SyncButton.vue';
import TechBadge from '@/shared/components/TechBadge.vue';
import { useConfirmDelete } from '@/shared/composables/useConfirmDelete';
import { useGlobalSync } from '@/shared/composables/useGlobalSync';
import DashboardLayout from '@/shared/layouts/DashboardLayout.vue';

const router = useRouter();
const { t } = useI18n();
const projectStore = useProjectStore();
const { onStepCompleted } = useGlobalSync();
const {
  cancel: cancelDelete,
  confirm: confirmDelete,
  isOpen: deleteOpen,
  requestDelete,
  target: deleteTarget,
} = useConfirmDelete<{ id: string; name: string }>();

const search = ref('');
const visibilityFilter = ref('');

const filteredProjects = computed(() => {
  return projectStore.projects.filter((p) => {
    if (search.value && !p.name.toLowerCase().includes(search.value.toLowerCase())) return false;
    if (visibilityFilter.value && p.visibility !== visibilityFilter.value) return false;
    return true;
  });
});

const hasActiveFilters = computed(() => search.value !== '' || visibilityFilter.value !== '');
const hasPagination = computed(() => projectStore.totalPages > 1);

onMounted(() => {
  projectStore.fetchAll();
});

function getDropdownItems() {
  return [
    { action: 'view', label: t('common.actions.view') },
    { action: 'delete', label: t('common.actions.delete'), variant: 'danger' as const },
  ];
}

function handleDropdownAction(action: string, project: Project) {
  if (action === 'view')
    router.push({ name: 'catalog-projects-detail', params: { id: project.id } });
  else if (action === 'delete') requestDelete({ id: project.id, name: project.name });
}

function navigateToDetail(id: string) {
  router.push({ name: 'catalog-projects-detail', params: { id } });
}

const MAX_BADGES = 5;

function changePage(page: number) {
  projectStore.fetchAll(page);
}

onStepCompleted((step) => {
  if (step === 'sync_projects') {
    projectStore.fetchAll();
  }
});

function getUniqueTechNames(project: Project): string[] {
  const names = new Set<string>();
  for (const ts of project.techStacks ?? []) {
    const name = ts.framework && ts.framework !== 'none' ? ts.framework : null;
    if (name) names.add(name);
  }
  return [...names];
}
</script>

<template>
  <DashboardLayout>
    <div data-testid="project-list-page">
      <nav
        class="mb-6 flex items-center gap-1 text-sm text-text-muted"
        data-testid="project-list-breadcrumb"
      >
        <span class="font-medium text-text">
          {{ t('catalog.projects.title') }}
        </span>
      </nav>

      <div class="mb-6 flex items-center justify-between">
        <h2 class="text-2xl font-bold text-text">
          {{ t('catalog.projects.title') }}
        </h2>
        <SyncButton />
      </div>

      <div
        v-if="projectStore.loading"
        class="py-8 text-center text-text-muted"
        data-testid="project-list-loading"
      >
        {{ t('common.actions.loading') }}
      </div>

      <div
        v-else-if="projectStore.error"
        class="rounded-lg bg-danger/10 p-4 text-danger"
        role="alert"
        data-testid="project-list-error"
      >
        {{ projectStore.error }}
      </div>

      <template v-else>
        <div
          v-if="projectStore.projects.length > 0"
          class="mb-4 flex flex-wrap items-center gap-3"
          data-testid="project-list-filters"
        >
          <div class="relative flex-1">
            <svg
              class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-text-muted"
              fill="none"
              stroke="currentColor"
              stroke-width="1.5"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"
              />
            </svg>
            <input
              v-model="search"
              type="search"
              :aria-label="t('catalog.projects.searchProjects')"
              :placeholder="t('catalog.projects.searchProjects')"
              class="w-full rounded-lg border border-border bg-surface py-2 pl-9 pr-3 text-sm text-text placeholder:text-text-muted focus:border-primary focus:outline-none"
              data-testid="project-search"
            />
          </div>
          <select
            v-model="visibilityFilter"
            :aria-label="t('catalog.projects.allVisibilities')"
            class="rounded-lg border border-border bg-surface px-3 py-2 text-sm text-text focus:border-primary focus:outline-none"
            data-testid="project-filter-visibility"
          >
            <option value="">
              {{ t('catalog.projects.allVisibilities') }}
            </option>
            <option value="public">
              {{ t('catalog.projects.visibilityPublic') }}
            </option>
            <option value="private">
              {{ t('catalog.projects.visibilityPrivate') }}
            </option>
          </select>
        </div>

        <div
          v-if="filteredProjects.length > 0"
          class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
          data-testid="project-list-grid"
        >
          <div
            v-for="project in filteredProjects"
            :key="project.id"
            class="cursor-pointer rounded-xl border border-border bg-surface p-5 shadow-sm transition-shadow hover:shadow-md"
            data-testid="project-list-card"
            role="link"
            tabindex="0"
            @click="navigateToDetail(project.id)"
            @keydown.enter="navigateToDetail(project.id)"
          >
            <div class="mb-3 flex items-start justify-between">
              <div class="min-w-0 flex-1">
                <h3 class="truncate text-sm font-semibold text-text">
                  {{ project.name }}
                </h3>
                <p class="truncate text-xs text-text-muted">
                  {{ project.repositoryUrl }}
                </p>
              </div>
              <div class="ml-2 flex shrink-0 items-center gap-2" @click.stop>
                <span
                  :class="[
                    'rounded-full px-2 py-0.5 text-xs font-medium',
                    project.visibility === 'public'
                      ? 'bg-success/10 text-success'
                      : 'bg-warning/10 text-warning',
                  ]"
                  data-testid="project-visibility-badge"
                >
                  {{ project.visibility }}
                </span>
                <DropdownMenu
                  :items="getDropdownItems()"
                  @select="handleDropdownAction($event, project)"
                />
              </div>
            </div>

            <div class="flex items-center justify-between border-t border-border pt-3">
              <div class="flex items-center gap-4">
                <div data-testid="project-branch">
                  <p class="text-sm font-medium text-text">
                    {{ project.defaultBranch }}
                  </p>
                  <p class="text-xs text-text-muted">
                    {{ t('catalog.projects.branch') }}
                  </p>
                </div>
                <div
                  v-if="getUniqueTechNames(project).length > 0"
                  class="flex flex-wrap gap-1.5"
                  data-testid="project-stacks-count"
                >
                  <TechBadge
                    v-for="name in getUniqueTechNames(project).slice(0, MAX_BADGES)"
                    :key="name"
                    :name="name"
                    size="sm"
                  />
                  <span
                    v-if="getUniqueTechNames(project).length > MAX_BADGES"
                    class="text-xs text-text-muted"
                  >
                    +{{ getUniqueTechNames(project).length - MAX_BADGES }}
                  </span>
                </div>
                <p v-else class="text-xs text-text-muted" data-testid="project-stacks-count">—</p>
              </div>
            </div>
          </div>
        </div>

        <div
          v-if="hasActiveFilters && filteredProjects.length === 0"
          class="flex flex-col items-center rounded-xl border border-border bg-surface py-12"
          data-testid="project-list-no-match"
        >
          <p class="text-sm text-text-muted">
            {{ t('catalog.projects.noMatchingProjects') }}
          </p>
        </div>

        <Pagination
          v-if="hasPagination"
          :page="projectStore.currentPage"
          :total-pages="projectStore.totalPages"
          data-testid="project-list-pagination"
          @update:page="changePage"
        />

        <div
          v-if="projectStore.projects.length === 0"
          class="flex flex-col items-center rounded-xl border border-border bg-surface py-12"
          data-testid="project-list-empty"
        >
          <svg
            class="mb-4 h-12 w-12 text-text-muted/50"
            fill="none"
            stroke="currentColor"
            stroke-width="1.5"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"
            />
          </svg>
          <p class="mb-1 text-sm font-medium text-text">
            {{ t('catalog.projects.noProjects') }}
          </p>
          <p class="mb-4 text-sm text-text-muted">
            {{ t('catalog.projects.noProjectsHint') }}
          </p>
          <RouterLink
            :to="{ name: 'catalog-providers-list' }"
            class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark"
            data-testid="project-empty-providers-link"
          >
            {{ t('catalog.providers.title') }}
          </RouterLink>
        </div>
      </template>

      <ConfirmDialog
        :open="deleteOpen"
        :title="t('catalog.projects.confirmDeleteTitle')"
        :message="t('catalog.projects.confirmDeleteMessage', { name: deleteTarget?.name ?? '' })"
        :confirm-label="t('common.actions.delete')"
        variant="danger"
        @confirm="confirmDelete(() => projectStore.remove(deleteTarget!.id))"
        @cancel="cancelDelete"
      />
    </div>
  </DashboardLayout>
</template>
