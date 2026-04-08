<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink, useRoute } from 'vue-router';

import { useProjectStore } from '@/catalog/stores/project';
import { useDependencyStore } from '@/dependency/stores/dependency';
import { useVulnerabilityStore } from '@/dependency/stores/vulnerability';
import DashboardLayout from '@/shared/layouts/DashboardLayout.vue';

const route = useRoute();
const { d, t } = useI18n();
const dependencyStore = useDependencyStore();
const projectStore = useProjectStore();
const vulnerabilityStore = useVulnerabilityStore();

const linkedVulnerabilities = computed(() =>
  vulnerabilityStore.vulnerabilities.filter(
    (v) => v.dependencyId === dependencyStore.selectedDependency?.id,
  ),
);

onMounted(async () => {
  const id = route.params.id as string;
  await dependencyStore.fetchOne(id);
  await Promise.all([
    dependencyStore.selectedDependency?.projectId
      ? projectStore.fetchOne(dependencyStore.selectedDependency.projectId)
      : Promise.resolve(),
    vulnerabilityStore.fetchAll(1, 200),
  ]);
});
</script>

<template>
  <DashboardLayout>
    <div data-testid="dependency-detail-page">
      <nav class="mb-6 flex items-center gap-1 text-sm text-text-muted">
        <RouterLink
          :to="{ name: 'dependency-dependencies-list' }"
          class="text-primary hover:text-primary-dark"
        >
          {{ t('dependency.dependencies.title') }}
        </RouterLink>
        <span>/</span>
        <span v-if="dependencyStore.selectedDependency" class="font-medium text-text">{{
          dependencyStore.selectedDependency.name
        }}</span>
      </nav>

      <div class="mb-6 flex items-center justify-between">
        <div />
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

      <div v-else-if="dependencyStore.selectedDependency" class="space-y-6">
        <div
          class="max-w-2xl rounded-xl border border-border bg-surface p-6"
          data-testid="dependency-detail-card"
        >
          <h2 class="mb-6 text-2xl font-bold text-text">
            {{ dependencyStore.selectedDependency.name }}
          </h2>

          <dl class="space-y-4">
            <div v-if="projectStore.selected">
              <dt class="text-sm font-medium text-text-muted">
                {{ t('catalog.techStacks.project') }}
              </dt>
              <dd class="mt-1">
                <RouterLink
                  :to="{
                    name: 'catalog-projects-detail',
                    params: { id: projectStore.selected.id },
                  }"
                  class="text-primary hover:text-primary-dark"
                  data-testid="dependency-detail-project"
                >
                  {{ projectStore.selected.name }}
                </RouterLink>
              </dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-text-muted">
                {{ t('dependency.dependencies.currentVersion') }}
              </dt>
              <dd class="mt-1 text-text" data-testid="dependency-detail-current-version">
                {{ dependencyStore.selectedDependency.currentVersion }}
              </dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-text-muted">
                {{ t('dependency.dependencies.latestVersion') }}
              </dt>
              <dd class="mt-1 text-text" data-testid="dependency-detail-latest-version">
                {{ dependencyStore.selectedDependency.latestVersion }}
              </dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-text-muted">
                {{ t('dependency.dependencies.ltsVersion') }}
              </dt>
              <dd class="mt-1 text-text" data-testid="dependency-detail-lts-version">
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
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-3.5 w-3.5"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                  >
                    <path
                      d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z"
                    />
                    <path
                      d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z"
                    />
                  </svg>
                </a>
                <span v-else class="text-text-muted" data-testid="dependency-detail-repository-url"
                  >—</span
                >
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
                    'bg-purple-100 text-purple-800':
                      dependencyStore.selectedDependency.type === 'runtime',
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
                  {{
                    dependencyStore.selectedDependency.isOutdated
                      ? t('dependency.dependencies.outdated')
                      : t('dependency.dependencies.upToDate')
                  }}
                </span>
              </dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-text-muted">
                {{ t('dependency.dependencies.vulnerabilities') }}
              </dt>
              <dd class="mt-1 text-text" data-testid="dependency-detail-vuln-count">
                {{ dependencyStore.selectedDependency.vulnerabilityCount }}
              </dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-text-muted">
                {{ t('common.createdAt') }}
              </dt>
              <dd class="mt-1 text-text" data-testid="dependency-detail-created-at">
                {{ d(new Date(dependencyStore.selectedDependency.createdAt), 'short') }}
              </dd>
            </div>
          </dl>
        </div>

        <div class="max-w-2xl">
          <h3 class="mb-4 text-lg font-semibold text-text">
            {{ t('dependency.dependencies.vulnerabilities') }} ({{ linkedVulnerabilities.length }})
          </h3>

          <div
            v-if="linkedVulnerabilities.length > 0"
            class="overflow-hidden rounded-xl border border-border bg-surface"
          >
            <table class="w-full">
              <thead>
                <tr class="border-b border-border bg-surface-muted">
                  <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                    {{ t('dependency.vulnerabilities.cveId') }}
                  </th>
                  <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                    {{ t('dependency.vulnerabilities.severity') }}
                  </th>
                  <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                    {{ t('dependency.vulnerabilities.vulnTitle') }}
                  </th>
                  <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                    {{ t('dependency.vulnerabilities.status') }}
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="vuln in linkedVulnerabilities"
                  :key="vuln.id"
                  class="border-b border-border last:border-0"
                >
                  <td class="px-4 py-3 text-sm">
                    <RouterLink
                      :to="{ name: 'dependency-vulnerabilities-detail', params: { id: vuln.id } }"
                      class="font-medium text-primary hover:text-primary-dark"
                    >
                      {{ vuln.cveId }}
                    </RouterLink>
                  </td>
                  <td class="px-4 py-3">
                    <span
                      :class="{
                        'bg-red-100 text-red-800': vuln.severity === 'critical',
                        'bg-orange-100 text-orange-800': vuln.severity === 'high',
                        'bg-yellow-100 text-yellow-800': vuln.severity === 'medium',
                        'bg-green-100 text-green-800': vuln.severity === 'low',
                      }"
                      class="rounded-full px-2 py-0.5 text-xs font-medium"
                    >
                      {{ vuln.severity }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-sm text-text">
                    {{ vuln.title }}
                  </td>
                  <td class="px-4 py-3">
                    <span
                      :class="{
                        'bg-red-100 text-red-800': vuln.status === 'open',
                        'bg-yellow-100 text-yellow-800': vuln.status === 'acknowledged',
                        'bg-green-100 text-green-800': vuln.status === 'fixed',
                        'bg-gray-100 text-gray-800': vuln.status === 'ignored',
                      }"
                      class="rounded-full px-2 py-0.5 text-xs font-medium"
                    >
                      {{ vuln.status }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <p v-else class="text-sm text-text-muted">
            {{ t('dependency.vulnerabilities.noVulnerabilities') }}
          </p>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
