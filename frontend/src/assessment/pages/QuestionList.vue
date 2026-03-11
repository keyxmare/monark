<script setup lang="ts">
import { onMounted } from 'vue'
import { RouterLink, useRoute } from 'vue-router'

import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'
import { useQuestionStore } from '@/assessment/stores/question'

const route = useRoute()
const questionStore = useQuestionStore()

onMounted(() => {
  const quizId = route.query.quiz_id as string | undefined
  questionStore.fetchAll(1, 20, quizId)
})

async function handleDelete(id: string) {
  await questionStore.remove(id)
}

function typeColor(type: string): string {
  switch (type) {
    case 'single_choice': return 'bg-primary/10 text-primary'
    case 'multiple_choice': return 'bg-info/10 text-info'
    case 'code': return 'bg-warning/10 text-warning'
    default: return 'bg-text-muted/10 text-text-muted'
  }
}

function levelColor(level: string): string {
  switch (level) {
    case 'easy': return 'bg-success/10 text-success'
    case 'hard': return 'bg-danger/10 text-danger'
    default: return 'bg-warning/10 text-warning'
  }
}
</script>

<template>
  <DashboardLayout>
    <div data-testid="question-list-page">
      <div class="mb-6 flex items-center justify-between">
        <h2 class="text-2xl font-bold text-text">
          Questions
        </h2>
        <RouterLink
          :to="{ name: 'assessment-questions-create' }"
          class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark"
          data-testid="question-create-link"
        >
          Create Question
        </RouterLink>
      </div>

      <div
        v-if="questionStore.loading"
        class="py-8 text-center text-text-muted"
        data-testid="question-list-loading"
      >
        Loading...
      </div>

      <div
        v-else-if="questionStore.error"
        class="rounded-lg bg-danger/10 p-4 text-danger"
        role="alert"
        data-testid="question-list-error"
      >
        {{ questionStore.error }}
      </div>

      <div
        v-else
        class="overflow-hidden rounded-xl border border-border bg-surface"
      >
        <table
          class="w-full"
          data-testid="question-list-table"
        >
          <thead>
            <tr class="border-b border-border bg-surface-muted">
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                Type
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                Content
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                Level
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                Score
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                Position
              </th>
              <th class="px-4 py-3 text-right text-sm font-medium text-text-muted">
                Actions
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="question in questionStore.questions"
              :key="question.id"
              class="border-b border-border last:border-0"
              data-testid="question-list-row"
            >
              <td class="px-4 py-3 text-sm">
                <span
                  :class="typeColor(question.type)"
                  class="rounded-full px-2 py-0.5 text-xs font-medium"
                  data-testid="question-type-badge"
                >
                  {{ question.type }}
                </span>
              </td>
              <td class="max-w-xs truncate px-4 py-3 text-sm text-text">
                {{ question.content }}
              </td>
              <td class="px-4 py-3 text-sm">
                <span
                  :class="levelColor(question.level)"
                  class="rounded-full px-2 py-0.5 text-xs font-medium"
                  data-testid="question-level-badge"
                >
                  {{ question.level }}
                </span>
              </td>
              <td class="px-4 py-3 text-sm text-text">
                {{ question.score }}
              </td>
              <td class="px-4 py-3 text-sm text-text-muted">
                {{ question.position }}
              </td>
              <td class="flex items-center justify-end gap-3 px-4 py-3">
                <RouterLink
                  :to="{ name: 'assessment-questions-detail', params: { id: question.id } }"
                  class="text-sm text-primary hover:text-primary-dark"
                  data-testid="question-view-link"
                >
                  View
                </RouterLink>
                <RouterLink
                  :to="{ name: 'assessment-questions-edit', params: { id: question.id } }"
                  class="text-sm text-primary hover:text-primary-dark"
                  data-testid="question-edit-link"
                >
                  Edit
                </RouterLink>
                <button
                  class="text-sm text-danger hover:text-danger/80"
                  data-testid="question-delete"
                  @click="handleDelete(question.id)"
                >
                  Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <div
          v-if="questionStore.questions.length === 0"
          class="py-8 text-center text-text-muted"
          data-testid="question-list-empty"
        >
          No questions found.
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
