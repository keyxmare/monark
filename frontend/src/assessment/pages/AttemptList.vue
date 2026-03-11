<script setup lang="ts">
import { onMounted } from 'vue'
import { RouterLink } from 'vue-router'

import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'
import { useAttemptStore } from '@/assessment/stores/attempt'

const attemptStore = useAttemptStore()

onMounted(() => {
  attemptStore.fetchAll()
})

function statusColor(status: string): string {
  switch (status) {
    case 'graded': return 'bg-success/10 text-success'
    case 'submitted': return 'bg-info/10 text-info'
    default: return 'bg-warning/10 text-warning'
  }
}
</script>

<template>
  <DashboardLayout>
    <div data-testid="attempt-list-page">
      <div class="mb-6">
        <h2 class="text-2xl font-bold text-text">
          Attempts
        </h2>
      </div>

      <div
        v-if="attemptStore.loading"
        class="py-8 text-center text-text-muted"
        data-testid="attempt-list-loading"
      >
        Loading...
      </div>

      <div
        v-else-if="attemptStore.error"
        class="rounded-lg bg-danger/10 p-4 text-danger"
        role="alert"
        data-testid="attempt-list-error"
      >
        {{ attemptStore.error }}
      </div>

      <div
        v-else
        class="overflow-hidden rounded-xl border border-border bg-surface"
      >
        <table
          class="w-full"
          data-testid="attempt-list-table"
        >
          <thead>
            <tr class="border-b border-border bg-surface-muted">
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                Quiz
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                Score
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                Status
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
              v-for="attempt in attemptStore.attempts"
              :key="attempt.id"
              class="border-b border-border last:border-0"
              data-testid="attempt-list-row"
            >
              <td class="px-4 py-3 text-sm text-text-muted">
                {{ attempt.quizId.substring(0, 8) }}...
              </td>
              <td class="px-4 py-3 text-sm text-text">
                {{ attempt.score }}
              </td>
              <td class="px-4 py-3 text-sm">
                <span
                  :class="statusColor(attempt.status)"
                  class="rounded-full px-2 py-0.5 text-xs font-medium"
                  data-testid="attempt-status-badge"
                >
                  {{ attempt.status }}
                </span>
              </td>
              <td class="px-4 py-3 text-sm text-text-muted">
                {{ new Date(attempt.startedAt).toLocaleString() }}
              </td>
              <td class="px-4 py-3 text-sm text-text-muted">
                {{ attempt.finishedAt ? new Date(attempt.finishedAt).toLocaleString() : '—' }}
              </td>
              <td class="px-4 py-3 text-right">
                <RouterLink
                  :to="{ name: 'assessment-attempts-detail', params: { id: attempt.id } }"
                  class="text-sm text-primary hover:text-primary-dark"
                  data-testid="attempt-view-link"
                >
                  View
                </RouterLink>
              </td>
            </tr>
          </tbody>
        </table>

        <div
          v-if="attemptStore.attempts.length === 0"
          class="py-8 text-center text-text-muted"
          data-testid="attempt-list-empty"
        >
          No attempts found.
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
