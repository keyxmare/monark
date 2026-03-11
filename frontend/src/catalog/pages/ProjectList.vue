<script setup lang="ts">
import { onMounted } from 'vue'
import { RouterLink } from 'vue-router'

import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'
import { useProjectStore } from '@/catalog/stores/project'

const projectStore = useProjectStore()

onMounted(() => {
  projectStore.fetchAll()
})

async function handleDelete(id: string) {
  await projectStore.remove(id)
}
</script>

<template>
  <DashboardLayout>
    <div data-testid="project-list-page">
      <div class="mb-6 flex items-center justify-between">
        <h2 class="text-2xl font-bold text-text">
          Projects
        </h2>
        <RouterLink
          :to="{ name: 'catalog-projects-create' }"
          class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark"
          data-testid="project-create-link"
        >
          Create Project
        </RouterLink>
      </div>

      <div
        v-if="projectStore.loading"
        class="py-8 text-center text-text-muted"
        data-testid="project-list-loading"
      >
        Loading...
      </div>

      <div
        v-else-if="projectStore.error"
        class="rounded-lg bg-danger/10 p-4 text-danger"
        role="alert"
        data-testid="project-list-error"
      >
        {{ projectStore.error }}
      </div>

      <div
        v-else
        class="overflow-hidden rounded-xl border border-border bg-surface"
      >
        <table
          class="w-full"
          data-testid="project-list-table"
        >
          <thead>
            <tr class="border-b border-border bg-surface-muted">
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                Name
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                Repository
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                Visibility
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                Branch
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                Tech Stacks
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                Pipelines
              </th>
              <th class="px-4 py-3 text-right text-sm font-medium text-text-muted">
                Actions
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="project in projectStore.projects"
              :key="project.id"
              class="border-b border-border last:border-0"
              data-testid="project-list-row"
            >
              <td class="px-4 py-3 text-sm text-text">
                {{ project.name }}
              </td>
              <td class="px-4 py-3 text-sm text-text-muted">
                {{ project.repositoryUrl }}
              </td>
              <td class="px-4 py-3">
                <span
                  :class="[
                    'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                    project.visibility === 'public'
                      ? 'bg-success/10 text-success'
                      : 'bg-warning/10 text-warning',
                  ]"
                  data-testid="project-visibility-badge"
                >
                  {{ project.visibility }}
                </span>
              </td>
              <td class="px-4 py-3 text-sm text-text-muted">
                {{ project.defaultBranch }}
              </td>
              <td class="px-4 py-3 text-sm text-text">
                {{ project.techStacksCount }}
              </td>
              <td class="px-4 py-3 text-sm text-text">
                {{ project.pipelinesCount }}
              </td>
              <td class="flex items-center justify-end gap-3 px-4 py-3">
                <RouterLink
                  :to="{ name: 'catalog-projects-detail', params: { id: project.id } }"
                  class="text-sm text-primary hover:text-primary-dark"
                  data-testid="project-view-link"
                >
                  View
                </RouterLink>
                <RouterLink
                  :to="{ name: 'catalog-projects-edit', params: { id: project.id } }"
                  class="text-sm text-primary hover:text-primary-dark"
                  data-testid="project-edit-link"
                >
                  Edit
                </RouterLink>
                <button
                  class="text-sm text-danger hover:text-danger/80"
                  data-testid="project-delete"
                  @click="handleDelete(project.id)"
                >
                  Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <div
          v-if="projectStore.projects.length === 0"
          class="py-8 text-center text-text-muted"
          data-testid="project-list-empty"
        >
          No projects found.
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
