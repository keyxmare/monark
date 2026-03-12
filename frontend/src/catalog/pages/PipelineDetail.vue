<script setup lang="ts">
import { onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, useRoute } from 'vue-router'

import { usePipelineStore } from '@/catalog/stores/pipeline'
import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'

const route = useRoute()
const { d, t } = useI18n()
const pipelineStore = usePipelineStore()

onMounted(() => {
  const id = route.params.id as string
  pipelineStore.fetchOne(id)
})
</script>

<template>
  <DashboardLayout>
    <div data-testid="pipeline-detail-page">
      <div class="mb-6">
        <RouterLink
          :to="{ name: 'catalog-pipelines-list' }"
          class="text-sm text-primary hover:text-primary-dark"
          data-testid="pipeline-detail-back"
        >
          &larr; {{ t('common.backTo', { page: t('catalog.pipelines.title').toLowerCase() }) }}
        </RouterLink>
      </div>

      <div
        v-if="pipelineStore.loading"
        class="py-8 text-center text-text-muted"
        data-testid="pipeline-detail-loading"
      >
        {{ t('common.actions.loading') }}
      </div>

      <div
        v-else-if="pipelineStore.error"
        class="rounded-lg bg-danger/10 p-4 text-danger"
        role="alert"
        data-testid="pipeline-detail-error"
      >
        {{ pipelineStore.error }}
      </div>

      <div
        v-else-if="pipelineStore.selected"
        class="max-w-2xl rounded-xl border border-border bg-surface p-6"
        data-testid="pipeline-detail-card"
      >
        <h2 class="mb-6 text-2xl font-bold text-text">
          {{ t('catalog.pipelines.pipelineNumber', { id: pipelineStore.selected.externalId }) }}
        </h2>

        <dl class="space-y-4">
          <div>
            <dt class="text-sm font-medium text-text-muted">
              {{ t('catalog.pipelines.externalId') }}
            </dt>
            <dd
              class="mt-1 text-text"
              data-testid="pipeline-detail-external-id"
            >
              {{ pipelineStore.selected.externalId }}
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-text-muted">
              {{ t('catalog.pipelines.ref') }}
            </dt>
            <dd
              class="mt-1 text-text"
              data-testid="pipeline-detail-ref"
            >
              {{ pipelineStore.selected.ref }}
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-text-muted">
              {{ t('catalog.pipelines.status') }}
            </dt>
            <dd class="mt-1">
              <span
                :class="[
                  'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                  {
                    'bg-warning/10 text-warning': pipelineStore.selected.status === 'pending',
                    'bg-info/10 text-info': pipelineStore.selected.status === 'running',
                    'bg-success/10 text-success': pipelineStore.selected.status === 'success',
                    'bg-danger/10 text-danger': pipelineStore.selected.status === 'failed',
                  },
                ]"
                data-testid="pipeline-detail-status"
              >
                {{ pipelineStore.selected.status }}
              </span>
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-text-muted">
              {{ t('catalog.pipelines.duration') }}
            </dt>
            <dd
              class="mt-1 text-text"
              data-testid="pipeline-detail-duration"
            >
              {{ pipelineStore.selected.duration }}s
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-text-muted">
              {{ t('catalog.pipelines.startedAt') }}
            </dt>
            <dd
              class="mt-1 text-text"
              data-testid="pipeline-detail-started-at"
            >
              {{ d(new Date(pipelineStore.selected.startedAt), 'long') }}
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-text-muted">
              {{ t('catalog.pipelines.finishedAt') }}
            </dt>
            <dd
              class="mt-1 text-text"
              data-testid="pipeline-detail-finished-at"
            >
              {{ pipelineStore.selected.finishedAt ? d(new Date(pipelineStore.selected.finishedAt), 'long') : t('catalog.pipelines.notFinished') }}
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-text-muted">
              {{ t('identity.users.createdAt') }}
            </dt>
            <dd
              class="mt-1 text-text"
              data-testid="pipeline-detail-created-at"
            >
              {{ d(new Date(pipelineStore.selected.createdAt), 'short') }}
            </dd>
          </div>
        </dl>
      </div>
    </div>
  </DashboardLayout>
</template>
