<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'

import { useSyncTaskStore } from '@/activity/stores/sync-task'
import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'

const { t, d } = useI18n()
const syncTaskStore = useSyncTaskStore()

const filterStatus = ref('')
const filterType = ref('')
const filterSeverity = ref('')

onMounted(async () => {
  await Promise.all([
    syncTaskStore.fetchAll(),
    syncTaskStore.fetchStats(),
  ])
})

async function applyFilters() {
  await syncTaskStore.fetchAll({
    status: filterStatus.value || undefined,
    type: filterType.value || undefined,
    severity: filterSeverity.value || undefined,
  })
}

async function handleStatusChange(id: string, status: string) {
  await syncTaskStore.updateStatus(id, status)
  await syncTaskStore.fetchStats()
}

function severityClass(severity: string): string {
  const classes: Record<string, string> = {
    critical: 'bg-red-100 text-red-800',
    high: 'bg-orange-100 text-orange-800',
    medium: 'bg-yellow-100 text-yellow-800',
    low: 'bg-blue-100 text-blue-800',
    info: 'bg-gray-100 text-gray-800',
  }
  return classes[severity] ?? 'bg-gray-100 text-gray-800'
}

function statusClass(status: string): string {
  const classes: Record<string, string> = {
    open: 'bg-red-100 text-red-800',
    acknowledged: 'bg-yellow-100 text-yellow-800',
    resolved: 'bg-green-100 text-green-800',
    dismissed: 'bg-gray-100 text-gray-800',
  }
  return classes[status] ?? 'bg-gray-100 text-gray-800'
}
</script>

<template>
  <DashboardLayout>
    <div data-testid="sync-task-list-page">
      <h2
        class="mb-6 text-2xl font-bold text-text"
        data-testid="sync-task-title"
      >
        {{ t('activity.syncTasks.title') }}
      </h2>

      <div
        v-if="syncTaskStore.stats"
        class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-5"
        data-testid="sync-task-stats"
      >
        <div
          v-for="entry in syncTaskStore.stats.bySeverity"
          :key="entry.label"
          class="rounded-xl border border-border bg-surface p-4 shadow-sm"
        >
          <p class="text-sm font-medium text-text-muted">
            {{ t(`activity.syncTasks.severity.${entry.label}`) }}
          </p>
          <p class="mt-1 text-2xl font-bold text-text">
            {{ entry.count }}
          </p>
        </div>
      </div>

      <div
        class="mb-4 flex flex-wrap items-center gap-3"
        data-testid="sync-task-filters"
      >
        <select
          v-model="filterStatus"
          class="rounded-lg border border-border bg-surface px-3 py-2 text-sm text-text"
          data-testid="filter-status"
          @change="applyFilters"
        >
          <option value="">
            {{ t('activity.syncTasks.allStatuses') }}
          </option>
          <option value="open">
            {{ t('activity.syncTasks.status.open') }}
          </option>
          <option value="acknowledged">
            {{ t('activity.syncTasks.status.acknowledged') }}
          </option>
          <option value="resolved">
            {{ t('activity.syncTasks.status.resolved') }}
          </option>
          <option value="dismissed">
            {{ t('activity.syncTasks.status.dismissed') }}
          </option>
        </select>
        <select
          v-model="filterType"
          class="rounded-lg border border-border bg-surface px-3 py-2 text-sm text-text"
          data-testid="filter-type"
          @change="applyFilters"
        >
          <option value="">
            {{ t('activity.syncTasks.allTypes') }}
          </option>
          <option value="outdated_dependency">
            {{ t('activity.syncTasks.type.outdated_dependency') }}
          </option>
          <option value="vulnerability">
            {{ t('activity.syncTasks.type.vulnerability') }}
          </option>
          <option value="stack_upgrade">
            {{ t('activity.syncTasks.type.stack_upgrade') }}
          </option>
          <option value="new_dependency">
            {{ t('activity.syncTasks.type.new_dependency') }}
          </option>
        </select>
        <select
          v-model="filterSeverity"
          class="rounded-lg border border-border bg-surface px-3 py-2 text-sm text-text"
          data-testid="filter-severity"
          @change="applyFilters"
        >
          <option value="">
            {{ t('activity.syncTasks.allSeverities') }}
          </option>
          <option value="critical">
            {{ t('activity.syncTasks.severity.critical') }}
          </option>
          <option value="high">
            {{ t('activity.syncTasks.severity.high') }}
          </option>
          <option value="medium">
            {{ t('activity.syncTasks.severity.medium') }}
          </option>
          <option value="low">
            {{ t('activity.syncTasks.severity.low') }}
          </option>
          <option value="info">
            {{ t('activity.syncTasks.severity.info') }}
          </option>
        </select>
      </div>

      <div
        v-if="syncTaskStore.loading"
        class="py-8 text-center text-text-muted"
        data-testid="sync-task-loading"
      >
        {{ t('common.actions.loading') }}
      </div>

      <div
        v-else-if="syncTaskStore.error"
        class="rounded-lg bg-danger/10 p-4 text-danger"
        role="alert"
        data-testid="sync-task-error"
      >
        {{ syncTaskStore.error }}
      </div>

      <div
        v-else
        class="overflow-x-auto rounded-xl border border-border bg-surface"
        data-testid="sync-task-table"
      >
        <table class="w-full text-left text-sm">
          <thead class="border-b border-border bg-background">
            <tr>
              <th class="px-4 py-3 font-medium text-text-muted">
                {{ t('activity.syncTasks.taskTitle') }}
              </th>
              <th class="px-4 py-3 font-medium text-text-muted">
                {{ t('activity.syncTasks.typeLabel') }}
              </th>
              <th class="px-4 py-3 font-medium text-text-muted">
                {{ t('activity.syncTasks.severityLabel') }}
              </th>
              <th class="px-4 py-3 font-medium text-text-muted">
                {{ t('activity.syncTasks.statusLabel') }}
              </th>
              <th class="px-4 py-3 font-medium text-text-muted">
                {{ t('activity.syncTasks.date') }}
              </th>
              <th class="px-4 py-3 font-medium text-text-muted">
                {{ t('common.table.actions') }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="task in syncTaskStore.tasks"
              :key="task.id"
              class="border-b border-border last:border-0"
              :data-testid="`sync-task-row-${task.id}`"
            >
              <td class="px-4 py-3">
                <p class="font-medium text-text">
                  {{ task.title }}
                </p>
                <p class="text-xs text-text-muted">
                  {{ task.description }}
                </p>
              </td>
              <td class="px-4 py-3">
                <span class="rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-800">
                  {{ t(`activity.syncTasks.type.${task.type}`) }}
                </span>
              </td>
              <td class="px-4 py-3">
                <span
                  :class="severityClass(task.severity)"
                  class="rounded-full px-2 py-0.5 text-xs font-medium"
                  :data-testid="`severity-badge-${task.severity}`"
                >
                  {{ t(`activity.syncTasks.severity.${task.severity}`) }}
                </span>
              </td>
              <td class="px-4 py-3">
                <span
                  :class="statusClass(task.status)"
                  class="rounded-full px-2 py-0.5 text-xs font-medium"
                >
                  {{ t(`activity.syncTasks.status.${task.status}`) }}
                </span>
              </td>
              <td class="px-4 py-3 text-text-muted">
                {{ d(new Date(task.createdAt), 'short') }}
              </td>
              <td class="px-4 py-3">
                <div class="flex gap-2">
                  <button
                    v-if="task.status === 'open'"
                    class="rounded bg-yellow-500 px-2 py-1 text-xs font-medium text-white hover:bg-yellow-600"
                    data-testid="action-acknowledge"
                    @click="handleStatusChange(task.id, 'acknowledged')"
                  >
                    {{ t('activity.syncTasks.acknowledge') }}
                  </button>
                  <button
                    v-if="task.status !== 'resolved'"
                    class="rounded bg-green-500 px-2 py-1 text-xs font-medium text-white hover:bg-green-600"
                    data-testid="action-resolve"
                    @click="handleStatusChange(task.id, 'resolved')"
                  >
                    {{ t('activity.syncTasks.resolve') }}
                  </button>
                  <button
                    v-if="task.status !== 'dismissed'"
                    class="rounded bg-gray-500 px-2 py-1 text-xs font-medium text-white hover:bg-gray-600"
                    data-testid="action-dismiss"
                    @click="handleStatusChange(task.id, 'dismissed')"
                  >
                    {{ t('activity.syncTasks.dismiss') }}
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <div
          v-if="syncTaskStore.tasks.length === 0"
          class="py-8 text-center text-text-muted"
          data-testid="sync-task-empty"
        >
          {{ t('activity.syncTasks.noTasks') }}
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
