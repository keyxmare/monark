<script setup lang="ts">
import { onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, useRoute } from 'vue-router'

import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'
import { useDependencyStore } from '@/dependency/stores/dependency'

const route = useRoute()
const { t } = useI18n()
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
          &larr; {{ t('common.backTo', { page: t('dependency.dependencies.title').toLowerCase() }) }}
        </RouterLink>
        <RouterLink
          v-if="dependencyStore.selectedDependency"
          :to="{ name: 'dependency-dependencies-edit', params: { id: dependencyStore.selectedDependency.id } }"
          class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark"
          data-testid="dependency-detail-edit"
        >
          {{ t('common.actions.edit') }}
        </RouterLink>
      </div>

      <div
        v-if="dependencyStore.loading"
        class="py-8 text-center text-text-muted"
        data-testid="dependency-detail-loading"
      >
        {{ t('common.actions.loading') }}
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
                {{ t('dependency.dependencies.currentVersion') }}
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
                {{ t('dependency.dependencies.latestVersion') }}
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
                {{ t('dependency.dependencies.ltsVersion') }}
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
                {{ t('dependency.dependencies.repository') }}
              </dt>
              <dd class="mt-1">
                <a
                  v-if="dependencyStore.selectedDependency.repositoryUrl"
                  :href="dependencyStore.selectedDependency.repositoryUrl"
                  target="_blank"
                  rel="noopener"
                  class="inline-flex items-center gap-1 text-primary hover:text-primary-dark"
                  data-testid="dependency-detail-repository-url"
                >
                  {{ dependencyStore.selectedDependency.repositoryUrl }}
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" />
                    <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z" />
                  </svg>
                </a>
                <span
                  v-else
                  class="text-text-muted"
                  data-testid="dependency-detail-repository-url"
                >—</span>
              </dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-text-muted">
                {{ t('dependency.dependencies.packageManager') }}
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
                {{ t('dependency.dependencies.type') }}
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
                {{ t('dependency.dependencies.status') }}
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
                  {{ dependencyStore.selectedDependency.isOutdated ? t('dependency.dependencies.outdated') : t('dependency.dependencies.upToDate') }}
                </span>
              </dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-text-muted">
                {{ t('dependency.dependencies.vulnerabilities') }}
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
                {{ t('common.createdAt') }}
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
              {{ t('dependency.dependencies.vulnerabilities') }}
            </h3>
            <RouterLink
              :to="{ name: 'dependency-vulnerabilities-create' }"
              class="text-sm text-primary hover:text-primary-dark"
              data-testid="dependency-detail-add-vuln"
            >
              {{ t('dependency.dependencies.addVulnerability') }}
            </RouterLink>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
