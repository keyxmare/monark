<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, useRoute } from 'vue-router'

import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'
import { useProjectStore } from '@/catalog/stores/project'
import { useTechStackStore } from '@/catalog/stores/tech-stack'
import { usePipelineStore } from '@/catalog/stores/pipeline'
import { useDependencyStore } from '@/dependency/stores/dependency'
import { useMergeRequestStore } from '@/catalog/stores/merge-request'

const route = useRoute()
const { t, d } = useI18n()
const projectStore = useProjectStore()
const techStackStore = useTechStackStore()
const pipelineStore = usePipelineStore()
const dependencyStore = useDependencyStore()
const mergeRequestStore = useMergeRequestStore()

const activeTab = ref<'tech-stacks' | 'pipelines' | 'dependencies' | 'merge-requests'>('tech-stacks')
const projectId = computed(() => route.params.id as string)

onMounted(async () => {
  await projectStore.fetchOne(projectId.value)
  await Promise.all([
    techStackStore.fetchAll(1, 20, projectId.value),
    pipelineStore.fetchAll(1, 10, projectId.value, projectStore.selected?.defaultBranch),
    dependencyStore.fetchAll(1, 100, projectId.value),
    mergeRequestStore.fetchAll(projectId.value),
  ])
})

async function handleDeleteTechStack(id: string) {
  await techStackStore.remove(id)
}

async function handleScan() {
  await projectStore.scan(projectId.value)
  await Promise.all([
    techStackStore.fetchAll(1, 100, projectId.value),
    dependencyStore.fetchAll(1, 100, projectId.value),
  ])
}
</script>

<template>
  <DashboardLayout>
    <div data-testid="project-detail-page">
      <div class="mb-6 flex items-center justify-between">
        <RouterLink
          :to="{ name: 'catalog-projects-list' }"
          class="text-sm text-primary hover:text-primary-dark"
          data-testid="project-detail-back"
        >
          &larr; {{ t('common.backTo', { page: t('catalog.projects.title').toLowerCase() }) }}
        </RouterLink>
        <div class="flex gap-2">
          <button
            v-if="projectStore.selected?.externalId"
            :disabled="projectStore.scanning"
            class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark disabled:opacity-50"
            data-testid="project-scan-btn"
            @click="handleScan"
          >
            {{ projectStore.scanning ? t('catalog.projects.scanning') : t('catalog.projects.scanProject') }}
          </button>
          <RouterLink
            v-if="projectStore.selected"
            :to="{ name: 'catalog-projects-edit', params: { id: projectStore.selected.id } }"
            class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark"
            data-testid="project-detail-edit"
          >
            {{ t('common.actions.edit') }}
          </RouterLink>
        </div>
      </div>

      <div
        v-if="projectStore.loading"
        class="py-8 text-center text-text-muted"
        data-testid="project-detail-loading"
      >
        {{ t('common.actions.loading') }}
      </div>

      <div
        v-else-if="projectStore.error"
        class="rounded-lg bg-danger/10 p-4 text-danger"
        role="alert"
        data-testid="project-detail-error"
      >
        {{ projectStore.error }}
      </div>

      <template v-else-if="projectStore.selected">
        <div
          class="mb-6 max-w-2xl rounded-xl border border-border bg-surface p-6"
          data-testid="project-detail-card"
        >
          <h2 class="mb-6 text-2xl font-bold text-text">
            {{ projectStore.selected.name }}
          </h2>

          <dl class="space-y-4">
            <div>
              <dt class="text-sm font-medium text-text-muted">
                {{ t('catalog.projects.slug') }}
              </dt>
              <dd
                class="mt-1 text-text"
                data-testid="project-detail-slug"
              >
                {{ projectStore.selected.slug }}
              </dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-text-muted">
                {{ t('catalog.projects.description') }}
              </dt>
              <dd
                class="mt-1 text-text"
                data-testid="project-detail-description"
              >
                {{ projectStore.selected.description ?? t('common.noDescription') }}
              </dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-text-muted">
                {{ t('catalog.projects.repositoryUrl') }}
              </dt>
              <dd
                class="mt-1 text-text"
                data-testid="project-detail-repository-url"
              >
                {{ projectStore.selected.repositoryUrl }}
              </dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-text-muted">
                {{ t('catalog.projects.defaultBranch') }}
              </dt>
              <dd
                class="mt-1 text-text"
                data-testid="project-detail-default-branch"
              >
                {{ projectStore.selected.defaultBranch }}
              </dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-text-muted">
                {{ t('catalog.projects.visibility') }}
              </dt>
              <dd class="mt-1">
                <span
                  :class="[
                    'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                    projectStore.selected.visibility === 'public'
                      ? 'bg-success/10 text-success'
                      : 'bg-warning/10 text-warning',
                  ]"
                  data-testid="project-detail-visibility"
                >
                  {{ projectStore.selected.visibility }}
                </span>
              </dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-text-muted">
                {{ t('identity.users.createdAt') }}
              </dt>
              <dd
                class="mt-1 text-text"
                data-testid="project-detail-created-at"
              >
                {{ d(new Date(projectStore.selected.createdAt), 'short') }}
              </dd>
            </div>
          </dl>
        </div>

        <div
          v-if="projectStore.scanResult"
          class="mb-6 rounded-lg border border-success/30 bg-success/10 p-4"
          data-testid="scan-result-banner"
        >
          <p class="text-sm font-medium text-success">
            {{ t('catalog.projects.scanComplete', { stacks: projectStore.scanResult.stacksDetected, deps: projectStore.scanResult.dependenciesDetected }) }}
          </p>
        </div>

        <div class="mb-4 flex gap-2 border-b border-border">
          <button
            :class="[
              'px-4 py-2 text-sm font-medium transition-colors',
              activeTab === 'tech-stacks'
                ? 'border-b-2 border-primary text-primary'
                : 'text-text-muted hover:text-text',
            ]"
            data-testid="tab-tech-stacks"
            @click="activeTab = 'tech-stacks'"
          >
            {{ t('catalog.projects.techStacksCount', { count: techStackStore.total }) }}
          </button>
          <button
            :class="[
              'px-4 py-2 text-sm font-medium transition-colors',
              activeTab === 'pipelines'
                ? 'border-b-2 border-primary text-primary'
                : 'text-text-muted hover:text-text',
            ]"
            data-testid="tab-pipelines"
            @click="activeTab = 'pipelines'"
          >
            {{ t('catalog.projects.pipelinesCount', { count: pipelineStore.total }) }}
          </button>
          <button
            :class="[
              'px-4 py-2 text-sm font-medium transition-colors',
              activeTab === 'dependencies'
                ? 'border-b-2 border-primary text-primary'
                : 'text-text-muted hover:text-text',
            ]"
            data-testid="tab-dependencies"
            @click="activeTab = 'dependencies'"
          >
            {{ t('catalog.projects.dependenciesCount', { count: dependencyStore.total }) }}
          </button>
          <button
            :class="[
              'px-4 py-2 text-sm font-medium transition-colors',
              activeTab === 'merge-requests'
                ? 'border-b-2 border-primary text-primary'
                : 'text-text-muted hover:text-text',
            ]"
            data-testid="tab-merge-requests"
            @click="activeTab = 'merge-requests'"
          >
            {{ t('catalog.projects.mergeRequestsCount', { count: mergeRequestStore.total }) }}
          </button>
        </div>

        <div
          v-if="activeTab === 'tech-stacks'"
          class="overflow-hidden rounded-xl border border-border bg-surface"
          data-testid="tech-stacks-panel"
        >
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
                  {{ t('catalog.techStacks.detectedAt') }}
                </th>
                <th class="px-4 py-3 text-right text-sm font-medium text-text-muted">
                  {{ t('common.table.actions') }}
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
                  {{ ts.frameworkVersion || '—' }}
                </td>
                <td class="px-4 py-3 text-sm text-text-muted">
                  {{ d(new Date(ts.detectedAt), 'short') }}
                </td>
                <td class="px-4 py-3 text-right">
                  <button
                    class="text-sm text-danger hover:text-danger/80"
                    data-testid="tech-stack-delete"
                    @click="handleDeleteTechStack(ts.id)"
                  >
                    {{ t('common.actions.delete') }}
                  </button>
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

        <div
          v-if="activeTab === 'pipelines'"
          class="overflow-hidden rounded-xl border border-border bg-surface"
          data-testid="pipelines-panel"
        >
          <table class="w-full">
            <thead>
              <tr class="border-b border-border bg-surface-muted">
                <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                  {{ t('catalog.pipelines.externalId') }}
                </th>
                <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                  {{ t('catalog.pipelines.ref') }}
                </th>
                <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                  {{ t('catalog.pipelines.status') }}
                </th>
                <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                  {{ t('catalog.pipelines.duration') }}
                </th>
                <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                  {{ t('catalog.pipelines.startedAt') }}
                </th>
                <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                  {{ t('catalog.pipelines.finishedAt') }}
                </th>
                <th class="px-4 py-3 text-right text-sm font-medium text-text-muted">
                  {{ t('common.table.actions') }}
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="pipeline in pipelineStore.pipelines"
                :key="pipeline.id"
                class="border-b border-border last:border-0"
                data-testid="pipeline-row"
              >
                <td class="px-4 py-3 text-sm text-text">
                  {{ pipeline.externalId }}
                </td>
                <td class="px-4 py-3 text-sm text-text-muted">
                  {{ pipeline.ref }}
                </td>
                <td class="px-4 py-3">
                  <span
                    :class="[
                      'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                      {
                        'bg-warning/10 text-warning': pipeline.status === 'pending',
                        'bg-info/10 text-info': pipeline.status === 'running',
                        'bg-success/10 text-success': pipeline.status === 'success',
                        'bg-danger/10 text-danger': pipeline.status === 'failed',
                      },
                    ]"
                    data-testid="pipeline-status-badge"
                  >
                    {{ pipeline.status }}
                  </span>
                </td>
                <td class="px-4 py-3 text-sm text-text-muted">
                  {{ pipeline.duration }}s
                </td>
                <td class="px-4 py-3 text-sm text-text-muted">
                  {{ d(new Date(pipeline.startedAt), 'short') }}
                </td>
                <td class="px-4 py-3 text-sm text-text-muted">
                  {{ pipeline.finishedAt ? d(new Date(pipeline.finishedAt), 'short') : '—' }}
                </td>
                <td class="px-4 py-3 text-right">
                  <RouterLink
                    :to="{ name: 'catalog-pipelines-detail', params: { id: pipeline.id } }"
                    class="text-sm text-primary hover:text-primary-dark"
                    data-testid="pipeline-view-link"
                  >
                    {{ t('common.actions.view') }}
                  </RouterLink>
                </td>
              </tr>
            </tbody>
          </table>
          <div
            v-if="pipelineStore.pipelines.length === 0"
            class="py-8 text-center text-text-muted"
            data-testid="pipelines-empty"
          >
            {{ t('catalog.projects.noPipelines') }}
          </div>
        </div>
        <div
          v-if="activeTab === 'dependencies'"
          class="overflow-hidden rounded-xl border border-border bg-surface"
          data-testid="dependencies-panel"
        >
          <table class="w-full">
            <thead>
              <tr class="border-b border-border bg-surface-muted">
                <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                  {{ t('catalog.projects.name') }}
                </th>
                <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                  {{ t('catalog.techStacks.version') }}
                </th>
                <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                  {{ t('dependency.dependencies.packageManager') }}
                </th>
                <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                  {{ t('dependency.dependencies.type') }}
                </th>
                <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                  {{ t('catalog.projects.repository') }}
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="dep in dependencyStore.dependencies"
                :key="dep.id"
                class="border-b border-border last:border-0"
                data-testid="dependency-row"
              >
                <td class="px-4 py-3 text-sm text-text">
                  {{ dep.name }}
                </td>
                <td class="px-4 py-3 text-sm text-text-muted">
                  {{ dep.currentVersion }}
                </td>
                <td class="px-4 py-3 text-sm text-text-muted">
                  {{ dep.packageManager }}
                </td>
                <td class="px-4 py-3 text-sm text-text-muted">
                  {{ dep.type }}
                </td>
                <td class="px-4 py-3 text-sm">
                  <a
                    v-if="dep.repositoryUrl"
                    :href="dep.repositoryUrl"
                    target="_blank"
                    rel="noopener"
                    class="text-primary hover:text-primary-dark"
                  >{{ t('catalog.projects.repoLink') }} ↗</a>
                  <span
                    v-else
                    class="text-text-muted"
                  >—</span>
                </td>
              </tr>
            </tbody>
          </table>
          <div
            v-if="dependencyStore.dependencies.length === 0"
            class="py-8 text-center text-text-muted"
            data-testid="dependencies-empty"
          >
            {{ t('catalog.projects.noDependencies') }}
          </div>
        </div>
        <div
          v-if="activeTab === 'merge-requests'"
          class="overflow-hidden rounded-xl border border-border bg-surface"
          data-testid="merge-requests-panel"
        >
          <table class="w-full">
            <thead>
              <tr class="border-b border-border bg-surface-muted">
                <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                  {{ t('catalog.mergeRequests.mrTitle') }}
                </th>
                <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                  {{ t('catalog.mergeRequests.status') }}
                </th>
                <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                  {{ t('catalog.mergeRequests.author') }}
                </th>
                <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                  {{ t('catalog.mergeRequests.branches') }}
                </th>
                <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                  {{ t('catalog.mergeRequests.updatedAt') }}
                </th>
                <th class="px-4 py-3 text-right text-sm font-medium text-text-muted">
                  {{ t('common.table.actions') }}
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="mr in mergeRequestStore.mergeRequests"
                :key="mr.id"
                class="border-b border-border last:border-0"
                data-testid="mr-row"
              >
                <td class="px-4 py-3 text-sm text-text">
                  <span class="text-text-muted">#{{ mr.externalId }}</span>
                  {{ mr.title }}
                </td>
                <td class="px-4 py-3">
                  <span
                    :class="[
                      'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                      {
                        'bg-success/10 text-success': mr.status === 'open',
                        'bg-info/10 text-info': mr.status === 'merged',
                        'bg-danger/10 text-danger': mr.status === 'closed',
                        'bg-warning/10 text-warning': mr.status === 'draft',
                      },
                    ]"
                    data-testid="mr-status-badge"
                  >
                    {{ mr.status }}
                  </span>
                </td>
                <td class="px-4 py-3 text-sm text-text-muted">
                  {{ mr.author }}
                </td>
                <td class="px-4 py-3 text-sm text-text-muted">
                  {{ mr.sourceBranch }} → {{ mr.targetBranch }}
                </td>
                <td class="px-4 py-3 text-sm text-text-muted">
                  {{ d(new Date(mr.updatedAt), 'short') }}
                </td>
                <td class="px-4 py-3 text-right">
                  <a
                    :href="mr.url"
                    target="_blank"
                    rel="noopener"
                    class="text-sm text-primary hover:text-primary-dark"
                    data-testid="mr-external-link"
                  >
                    {{ t('catalog.mergeRequests.viewExternal') }} ↗
                  </a>
                </td>
              </tr>
            </tbody>
          </table>
          <div
            v-if="mergeRequestStore.mergeRequests.length === 0"
            class="py-8 text-center text-text-muted"
            data-testid="merge-requests-empty"
          >
            {{ t('catalog.mergeRequests.noMergeRequests') }}
          </div>
        </div>
      </template>
    </div>
  </DashboardLayout>
</template>
