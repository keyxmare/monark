<script setup lang="ts">
import { onMounted } from 'vue'
import { RouterLink } from 'vue-router'

import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'
import { useQuizStore } from '@/assessment/stores/quiz'

const quizStore = useQuizStore()

onMounted(() => {
  quizStore.fetchAll()
})

async function handleDelete(id: string) {
  await quizStore.remove(id)
}

function statusColor(status: string): string {
  switch (status) {
    case 'published': return 'bg-success/10 text-success'
    case 'archived': return 'bg-text-muted/10 text-text-muted'
    default: return 'bg-warning/10 text-warning'
  }
}

function typeColor(type: string): string {
  return type === 'survey' ? 'bg-info/10 text-info' : 'bg-primary/10 text-primary'
}
</script>

<template>
  <DashboardLayout>
    <div data-testid="quiz-list-page">
      <div class="mb-6 flex items-center justify-between">
        <h2 class="text-2xl font-bold text-text">
          Quizzes
        </h2>
        <RouterLink
          :to="{ name: 'assessment-quizzes-create' }"
          class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark"
          data-testid="quiz-create-link"
        >
          Create Quiz
        </RouterLink>
      </div>

      <div
        v-if="quizStore.loading"
        class="py-8 text-center text-text-muted"
        data-testid="quiz-list-loading"
      >
        Loading...
      </div>

      <div
        v-else-if="quizStore.error"
        class="rounded-lg bg-danger/10 p-4 text-danger"
        role="alert"
        data-testid="quiz-list-error"
      >
        {{ quizStore.error }}
      </div>

      <div
        v-else
        class="overflow-hidden rounded-xl border border-border bg-surface"
      >
        <table
          class="w-full"
          data-testid="quiz-list-table"
        >
          <thead>
            <tr class="border-b border-border bg-surface-muted">
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                Title
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                Type
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                Status
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                Starts At
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                Ends At
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                Questions
              </th>
              <th class="px-4 py-3 text-right text-sm font-medium text-text-muted">
                Actions
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="quiz in quizStore.quizzes"
              :key="quiz.id"
              class="border-b border-border last:border-0"
              data-testid="quiz-list-row"
            >
              <td class="px-4 py-3 text-sm text-text">
                {{ quiz.title }}
              </td>
              <td class="px-4 py-3 text-sm">
                <span
                  :class="typeColor(quiz.type)"
                  class="rounded-full px-2 py-0.5 text-xs font-medium"
                  data-testid="quiz-type-badge"
                >
                  {{ quiz.type }}
                </span>
              </td>
              <td class="px-4 py-3 text-sm">
                <span
                  :class="statusColor(quiz.status)"
                  class="rounded-full px-2 py-0.5 text-xs font-medium"
                  data-testid="quiz-status-badge"
                >
                  {{ quiz.status }}
                </span>
              </td>
              <td class="px-4 py-3 text-sm text-text-muted">
                {{ quiz.startsAt ? new Date(quiz.startsAt).toLocaleDateString() : '—' }}
              </td>
              <td class="px-4 py-3 text-sm text-text-muted">
                {{ quiz.endsAt ? new Date(quiz.endsAt).toLocaleDateString() : '—' }}
              </td>
              <td class="px-4 py-3 text-sm text-text">
                {{ quiz.questionCount }}
              </td>
              <td class="flex items-center justify-end gap-3 px-4 py-3">
                <RouterLink
                  :to="{ name: 'assessment-quizzes-detail', params: { id: quiz.id } }"
                  class="text-sm text-primary hover:text-primary-dark"
                  data-testid="quiz-view-link"
                >
                  View
                </RouterLink>
                <RouterLink
                  :to="{ name: 'assessment-quizzes-edit', params: { id: quiz.id } }"
                  class="text-sm text-primary hover:text-primary-dark"
                  data-testid="quiz-edit-link"
                >
                  Edit
                </RouterLink>
                <button
                  class="text-sm text-danger hover:text-danger/80"
                  data-testid="quiz-delete"
                  @click="handleDelete(quiz.id)"
                >
                  Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <div
          v-if="quizStore.quizzes.length === 0"
          class="py-8 text-center text-text-muted"
          data-testid="quiz-list-empty"
        >
          No quizzes found.
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
