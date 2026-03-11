<script setup lang="ts">
import { onMounted } from 'vue'
import { RouterLink, useRoute } from 'vue-router'

import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'
import { useDependencyStore } from '@/dependency/stores/dependency'

const route = useRoute()
const dependencyStore = useDependencyStore()

onMounted(() => {
  const id = route.params.id as string
  dependencyStore.fetchOne(id)
})
</script>

<template>
  <DashboardLayout>
    <div data-testid="dependency-detail-page">
      <div class="mb-6 flex items-center justify-between">
        <RouterLink
          :to="{ name: 'dependency-dependencies-list' }"
          class="text-sm text-primary hover:text-primary-dark"
          data-testid="dependency-detail-back"
        >
          &larr; Back to dependencies
        </RouterLink>
        <RouterLink
          v-if="dependencyStore.selectedDependency"
          :to="{ name: 'dependency-dependencies-edit', params: { id: dependencyStore.selectedDependency.id } }"
          class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark"
          data-testid="dependency-detail-edit"
        >
          Edit
        </RouterLink>
      </div>

      <div
        v-if="dependencyStore.loading"
        class="py-8 text-center text-text-muted"
        data-testid="dependency-detail-loading"
      >
        Loading...
      </div>

      <div
        v-else-if="dependencyStore.error"
        class="rounded-lg bg-danger/10 p-4 text-danger"
        role="alert"
        data-testid="dependency-detail-error"
      >
        {{ dependencyStore.error }}
      </div>

      <div
        v-else-if="dependencyStore.selectedDependency"
        class="space-y-6"
      >
        <div
          class="max-w-2xl rounded-xl border border-border bg-surface p-6"
          data-testid="dependency-detail-card"
        >
          <h2 class="mb-6 text-2xl font-bold text-text">
            {{ dependencyStore.selectedDependency.name }}
          </h2>

          <dl class="space-y-4">
            <div>
              <dt class="text-sm font-medium text-text-muted">
                Current Version
              </dt>
              <dd
                class="mt-1 text-text"
                data-testid="dependency-detail-current-version"
              >
                {{ dependencyStore.selectedDependency.currentVersion }}
              </dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-text-muted">
                Latest Version
              </dt>
              <dd
                class="mt-1 text-text"
                data-testid="dependency-detail-latest-version"
              >
                {{ dependencyStore.selectedDependency.latestVersion }}
              </dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-text-muted">
                LTS Version
              </dt>
              <dd
                class="mt-1 text-text"
                data-testid="dependency-detail-lts-version"
              >
                {{ dependencyStore.selectedDependency.ltsVersion }}
              </dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-text-muted">
                Package Manager
              </dt>
              <dd class="mt-1">
                <span
                  class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800"
                  data-testid="dependency-detail-package-manager"
                >
                  {{ dependencyStore.selectedDependency.packageManager }}
                </span>
              </dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-text-muted">
                Type
              </dt>
              <dd class="mt-1">
                <span
                  :class="{
                    'bg-purple-100 text-purple-800': dependencyStore.selectedDependency.type === 'runtime',
                    'bg-gray-100 text-gray-800': dependencyStore.selectedDependency.type === 'dev',
                  }"
                  class="rounded-full px-2 py-0.5 text-xs font-medium"
                  data-testid="dependency-detail-type"
                >
                  {{ dependencyStore.selectedDependency.type }}
                </span>
              </dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-text-muted">
                Status
              </dt>
              <dd class="mt-1">
                <span
                  :class="{
                    'bg-red-100 text-red-800': dependencyStore.selectedDependency.isOutdated,
                    'bg-green-100 text-green-800': !dependencyStore.selectedDependency.isOutdated,
                  }"
                  class="rounded-full px-2 py-0.5 text-xs font-medium"
                  data-testid="dependency-detail-outdated"
                >
                  {{ dependencyStore.selectedDependency.isOutdated ? 'Outdated' : 'Up to date' }}
                </span>
              </dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-text-muted">
                Vulnerabilities
              </dt>
              <dd
                class="mt-1 text-text"
                data-testid="dependency-detail-vuln-count"
              >
                {{ dependencyStore.selectedDependency.vulnerabilityCount }}
              </dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-text-muted">
                Created At
              </dt>
              <dd
                class="mt-1 text-text"
                data-testid="dependency-detail-created-at"
              >
                {{ new Date(dependencyStore.selectedDependency.createdAt).toLocaleDateString() }}
              </dd>
            </div>
          </dl>
        </div>

        <div class="max-w-2xl">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-text">
              Vulnerabilities
            </h3>
            <RouterLink
              :to="{ name: 'dependency-vulnerabilities-create' }"
              class="text-sm text-primary hover:text-primary-dark"
              data-testid="dependency-detail-add-vuln"
            >
              Add vulnerability
            </RouterLink>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
