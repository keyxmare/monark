<script setup lang="ts">
import { onMounted } from 'vue'
import { RouterLink } from 'vue-router'

import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'
import { useDependencyStore } from '@/dependency/stores/dependency'

const dependencyStore = useDependencyStore()

onMounted(() => {
  dependencyStore.fetchAll()
})

async function handleDelete(id: string) {
  await dependencyStore.remove(id)
}
</script>

<template>
  <DashboardLayout>
    <div data-testid="dependency-list-page">
      <div class="mb-6 flex items-center justify-between">
        <h2 class="text-2xl font-bold text-text">
          Dependencies
        </h2>
        <RouterLink
          :to="{ name: 'dependency-dependencies-create' }"
          class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark"
          data-testid="dependency-create-link"
        >
          Create Dependency
        </RouterLink>
      </div>

      <div
        v-if="dependencyStore.loading"
        class="py-8 text-center text-text-muted"
        data-testid="dependency-list-loading"
      >
        Loading...
      </div>

      <div
        v-else-if="dependencyStore.error"
        class="rounded-lg bg-danger/10 p-4 text-danger"
        role="alert"
        data-testid="dependency-list-error"
      >
        {{ dependencyStore.error }}
      </div>

      <div
        v-else
        class="overflow-hidden rounded-xl border border-border bg-surface"
      >
        <table
          class="w-full"
          data-testid="dependency-list-table"
        >
          <thead>
            <tr class="border-b border-border bg-surface-muted">
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                Name
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                Current
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                Latest
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                Package Manager
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                Type
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                Status
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                Repository
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                Vulnerabilities
              </th>
              <th class="px-4 py-3 text-right text-sm font-medium text-text-muted">
                Actions
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="dep in dependencyStore.dependencies"
              :key="dep.id"
              class="border-b border-border last:border-0"
              data-testid="dependency-list-row"
            >
              <td class="px-4 py-3 text-sm font-medium text-text">
                {{ dep.name }}
              </td>
              <td class="px-4 py-3 text-sm text-text-muted">
                {{ dep.currentVersion }}
              </td>
              <td class="px-4 py-3 text-sm text-text-muted">
                {{ dep.latestVersion }}
              </td>
              <td class="px-4 py-3">
                <span
                  class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800"
                  data-testid="dependency-package-manager-badge"
                >
                  {{ dep.packageManager }}
                </span>
              </td>
              <td class="px-4 py-3">
                <span
                  :class="{
                    'bg-purple-100 text-purple-800': dep.type === 'runtime',
                    'bg-gray-100 text-gray-800': dep.type === 'dev',
                  }"
                  class="rounded-full px-2 py-0.5 text-xs font-medium"
                  data-testid="dependency-type-badge"
                >
                  {{ dep.type }}
                </span>
              </td>
              <td class="px-4 py-3">
                <span
                  :class="{
                    'bg-red-100 text-red-800': dep.isOutdated,
                    'bg-green-100 text-green-800': !dep.isOutdated,
                  }"
                  class="rounded-full px-2 py-0.5 text-xs font-medium"
                  data-testid="dependency-outdated-badge"
                >
                  {{ dep.isOutdated ? 'Outdated' : 'Up to date' }}
                </span>
              </td>
              <td class="px-4 py-3 text-sm">
                <a
                  v-if="dep.repositoryUrl"
                  :href="dep.repositoryUrl"
                  target="_blank"
                  rel="noopener"
                  class="inline-flex items-center gap-1 text-primary hover:text-primary-dark"
                  data-testid="dependency-repo-link"
                >
                  <span>Repo</span>
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" />
                    <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z" />
                  </svg>
                </a>
                <span
                  v-else
                  class="text-text-muted"
                  data-testid="dependency-repo-empty"
                >—</span>
              </td>
              <td class="px-4 py-3 text-sm text-text">
                {{ dep.vulnerabilityCount }}
              </td>
              <td class="flex items-center justify-end gap-3 px-4 py-3">
                <RouterLink
                  :to="{ name: 'dependency-dependencies-detail', params: { id: dep.id } }"
                  class="text-sm text-primary hover:text-primary-dark"
                  data-testid="dependency-view-link"
                >
                  View
                </RouterLink>
                <RouterLink
                  :to="{ name: 'dependency-dependencies-edit', params: { id: dep.id } }"
                  class="text-sm text-primary hover:text-primary-dark"
                  data-testid="dependency-edit-link"
                >
                  Edit
                </RouterLink>
                <button
                  class="text-sm text-danger hover:text-danger/80"
                  data-testid="dependency-delete"
                  @click="handleDelete(dep.id)"
                >
                  Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <div
          v-if="dependencyStore.dependencies.length === 0"
          class="py-8 text-center text-text-muted"
          data-testid="dependency-list-empty"
        >
          No dependencies found.
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
