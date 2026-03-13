<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, useRouter } from 'vue-router'

import type { Project } from '@/catalog/types/project'

import { useProjectStore } from '@/catalog/stores/project'
import ConfirmDialog from '@/shared/components/ConfirmDialog.vue'
import DropdownMenu from '@/shared/components/DropdownMenu.vue'
import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'

const router = useRouter()
const { t } = useI18n()
const projectStore = useProjectStore()
const deleteTarget = ref<null | { id: string; name: string }>(null)

const hasPagination = computed(() => projectStore.totalPages > 1)

onMounted(() => {
  projectStore.fetchAll()
})

function getDropdownItems() {
  return [
    { action: 'view', label: t('common.actions.view') },
    { action: 'edit', label: t('common.actions.edit') },
    { action: 'delete', label: t('common.actions.delete'), variant: 'danger' as const },
  ]
}

function handleDropdownAction(action: string, project: Project) {
  if (action === 'view') router.push({ name: 'catalog-projects-detail', params: { id: project.id } })
  else if (action === 'edit') router.push({ name: 'catalog-projects-edit', params: { id: project.id } })
  else if (action === 'delete') deleteTarget.value = { id: project.id, name: project.name }
}

async function confirmDelete() {
  if (!deleteTarget.value) return
  await projectStore.remove(deleteTarget.value.id)
  deleteTarget.value = null
}

function navigateToDetail(id: string) {
  router.push({ name: 'catalog-projects-detail', params: { id } })
}

function changePage(page: number) {
  projectStore.fetchAll(page)
}
</script>

<template>
  <DashboardLayout>
    <div data-testid="project-list-page">
      <div class="mb-6 flex items-center justify-between">
        <h2 class="text-2xl font-bold text-text">
          {{ t('catalog.projects.title') }}
        </h2>
        <RouterLink
          :to="{ name: 'catalog-projects-create' }"
          class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark"
          data-testid="project-create-link"
        >
          {{ t('catalog.projects.createProject') }}
        </RouterLink>
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
          class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
          data-testid="project-list-grid"
        >
          <div
            v-for="project in projectStore.projects"
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
              <div
                class="ml-2 flex shrink-0 items-center gap-2"
                @click.stop
              >
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
                <div data-testid="project-stacks-count">
                  <p class="text-sm font-bold tabular-nums text-text">
                    {{ project.techStacksCount }}
                  </p>
                  <p class="text-xs text-text-muted">
                    {{ t('catalog.projects.techStacks') }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div
          v-if="hasPagination"
          class="mt-6 flex items-center justify-center gap-2"
          data-testid="project-list-pagination"
        >
          <button
            :disabled="projectStore.currentPage <= 1"
            class="rounded-lg border border-border px-3 py-1.5 text-sm text-text transition-colors hover:bg-background disabled:opacity-50"
            data-testid="pagination-prev"
            @click="changePage(projectStore.currentPage - 1)"
          >
            {{ t('common.pagination.previous') }}
          </button>
          <span class="text-sm text-text-muted">
            {{ t('common.pagination.page', { current: projectStore.currentPage, total: projectStore.totalPages }) }}
          </span>
          <button
            :disabled="projectStore.currentPage >= projectStore.totalPages"
            class="rounded-lg border border-border px-3 py-1.5 text-sm text-text transition-colors hover:bg-background disabled:opacity-50"
            data-testid="pagination-next"
            @click="changePage(projectStore.currentPage + 1)"
          >
            {{ t('common.pagination.next') }}
          </button>
        </div>

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
            :to="{ name: 'catalog-projects-create' }"
            class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark"
            data-testid="project-empty-create-link"
          >
            {{ t('catalog.projects.createProject') }}
          </RouterLink>
        </div>
      </template>

      <ConfirmDialog
        :open="deleteTarget !== null"
        :title="t('catalog.projects.confirmDeleteTitle')"
        :message="t('catalog.projects.confirmDeleteMessage', { name: deleteTarget?.name ?? '' })"
        :confirm-label="t('common.actions.delete')"
        variant="danger"
        @confirm="confirmDelete"
        @cancel="deleteTarget = null"
      />
    </div>
  </DashboardLayout>
</template>
