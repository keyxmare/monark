<script setup lang="ts">
import { onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, useRoute } from 'vue-router'

import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'
import { useTechStackStore } from '@/catalog/stores/tech-stack'

const route = useRoute()
const { t, d } = useI18n()
const techStackStore = useTechStackStore()

const projectId = route.query.project_id as string | undefined

onMounted(() => {
  techStackStore.fetchAll(1, 20, projectId)
})

async function handleDelete(id: string) {
  await techStackStore.remove(id)
}
</script>

<template>
  <DashboardLayout>
    <div data-testid="tech-stack-list-page">
      <div class="mb-6 flex items-center justify-between">
        <h2 class="text-2xl font-bold text-text">
          {{ t('catalog.techStacks.title') }}
        </h2>
        <RouterLink
          :to="{ name: 'catalog-tech-stacks-create' }"
          class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark"
          data-testid="tech-stack-create-link"
        >
          {{ t('catalog.techStacks.addTechStack') }}
        </RouterLink>
      </div>

      <div
        v-if="techStackStore.loading"
        class="py-8 text-center text-text-muted"
        data-testid="tech-stack-list-loading"
      >
        {{ t('common.actions.loading') }}
      </div>

      <div
        v-else-if="techStackStore.error"
        class="rounded-lg bg-danger/10 p-4 text-danger"
        role="alert"
        data-testid="tech-stack-list-error"
      >
        {{ techStackStore.error }}
      </div>

      <div
        v-else
        class="overflow-hidden rounded-xl border border-border bg-surface"
      >
        <table
          class="w-full"
          data-testid="tech-stack-list-table"
        >
          <thead>
            <tr class="border-b border-border bg-surface-muted">
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                {{ t('catalog.techStacks.language') }}
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                {{ t('catalog.techStacks.framework') }}
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                {{ t('catalog.techStacks.version') }}
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
              data-testid="tech-stack-list-row"
            >
              <td class="px-4 py-3 text-sm text-text">
                {{ ts.language }}
              </td>
              <td class="px-4 py-3 text-sm text-text">
                {{ ts.framework }}
              </td>
              <td class="px-4 py-3 text-sm text-text-muted">
                {{ ts.version }}
              </td>
              <td class="px-4 py-3 text-sm text-text-muted">
                {{ d(new Date(ts.detectedAt), 'short') }}
              </td>
              <td class="px-4 py-3 text-right">
                <button
                  class="text-sm text-danger hover:text-danger/80"
                  data-testid="tech-stack-delete"
                  @click="handleDelete(ts.id)"
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
          data-testid="tech-stack-list-empty"
        >
          {{ t('catalog.techStacks.noTechStacks') }}
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
