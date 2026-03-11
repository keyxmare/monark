<script setup lang="ts">
import { onMounted } from 'vue'
import { RouterLink, useRoute } from 'vue-router'

import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'
import { usePipelineStore } from '@/catalog/stores/pipeline'

const route = useRoute()
const pipelineStore = usePipelineStore()

const projectId = route.query.project_id as string | undefined

onMounted(() => {
  pipelineStore.fetchAll(1, 20, projectId)
})
</script>

<template>
  <DashboardLayout>
    <div data-testid="pipeline-list-page">
      <div class="mb-6">
        <h2 class="text-2xl font-bold text-text">
          Pipelines
        </h2>
      </div>

      <div
        v-if="pipelineStore.loading"
        class="py-8 text-center text-text-muted"
        data-testid="pipeline-list-loading"
      >
        Loading...
      </div>

      <div
        v-else-if="pipelineStore.error"
        class="rounded-lg bg-danger/10 p-4 text-danger"
        role="alert"
        data-testid="pipeline-list-error"
      >
        {{ pipelineStore.error }}
      </div>

      <div
        v-else
        class="overflow-hidden rounded-xl border border-border bg-surface"
      >
        <table
          class="w-full"
          data-testid="pipeline-list-table"
        >
          <thead>
            <tr class="border-b border-border bg-surface-muted">
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                External ID
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                Ref
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                Status
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                Duration
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                Started At
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                Finished At
              </th>
              <th class="px-4 py-3 text-right text-sm font-medium text-text-muted">
                Actions
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="pipeline in pipelineStore.pipelines"
              :key="pipeline.id"
              class="border-b border-border last:border-0"
              data-testid="pipeline-list-row"
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
                {{ new Date(pipeline.startedAt).toLocaleDateString() }}
              </td>
              <td class="px-4 py-3 text-sm text-text-muted">
                {{ pipeline.finishedAt ? new Date(pipeline.finishedAt).toLocaleDateString() : '—' }}
              </td>
              <td class="px-4 py-3 text-right">
                <RouterLink
                  :to="{ name: 'catalog-pipelines-detail', params: { id: pipeline.id } }"
                  class="text-sm text-primary hover:text-primary-dark"
                  data-testid="pipeline-view-link"
                >
                  View
                </RouterLink>
              </td>
            </tr>
          </tbody>
        </table>

        <div
          v-if="pipelineStore.pipelines.length === 0"
          class="py-8 text-center text-text-muted"
          data-testid="pipeline-list-empty"
        >
          No pipelines found.
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
