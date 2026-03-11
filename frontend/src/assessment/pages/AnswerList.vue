<script setup lang="ts">
import { onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, useRoute } from 'vue-router'

import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'
import { useAnswerStore } from '@/assessment/stores/answer'

const route = useRoute()
const { t } = useI18n()
const answerStore = useAnswerStore()

onMounted(() => {
  const questionId = route.query.question_id as string | undefined
  answerStore.fetchAll(1, 20, questionId)
})

async function handleDelete(id: string) {
  await answerStore.remove(id)
}
</script>

<template>
  <DashboardLayout>
    <div data-testid="answer-list-page">
      <div class="mb-6 flex items-center justify-between">
        <h2 class="text-2xl font-bold text-text">
          {{ t('assessment.answers.title') }}
        </h2>
        <RouterLink
          :to="{ name: 'assessment-answers-create' }"
          class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark"
          data-testid="answer-create-link"
        >
          {{ t('assessment.answers.createAnswer') }}
        </RouterLink>
      </div>

      <div
        v-if="answerStore.loading"
        class="py-8 text-center text-text-muted"
        data-testid="answer-list-loading"
      >
        {{ t('common.actions.loading') }}
      </div>

      <div
        v-else-if="answerStore.error"
        class="rounded-lg bg-danger/10 p-4 text-danger"
        role="alert"
        data-testid="answer-list-error"
      >
        {{ answerStore.error }}
      </div>

      <div
        v-else
        class="overflow-hidden rounded-xl border border-border bg-surface"
      >
        <table
          class="w-full"
          data-testid="answer-list-table"
        >
          <thead>
            <tr class="border-b border-border bg-surface-muted">
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                {{ t('assessment.answers.content') }}
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                {{ t('assessment.answers.correct') }}
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                {{ t('assessment.answers.position') }}
              </th>
              <th class="px-4 py-3 text-right text-sm font-medium text-text-muted">
                {{ t('common.table.actions') }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="answer in answerStore.answers"
              :key="answer.id"
              class="border-b border-border last:border-0"
              data-testid="answer-list-row"
            >
              <td class="px-4 py-3 text-sm text-text">
                {{ answer.content }}
              </td>
              <td class="px-4 py-3 text-sm">
                <span
                  :class="answer.isCorrect ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger'"
                  class="rounded-full px-2 py-0.5 text-xs font-medium"
                  data-testid="answer-correct-badge"
                >
                  {{ answer.isCorrect ? t('common.confirm.yes') : t('common.confirm.no') }}
                </span>
              </td>
              <td class="px-4 py-3 text-sm text-text-muted">
                {{ answer.position }}
              </td>
              <td class="flex items-center justify-end gap-3 px-4 py-3">
                <RouterLink
                  :to="{ name: 'assessment-answers-edit', params: { id: answer.id } }"
                  class="text-sm text-primary hover:text-primary-dark"
                  data-testid="answer-edit-link"
                >
                  {{ t('common.actions.edit') }}
                </RouterLink>
                <button
                  class="text-sm text-danger hover:text-danger/80"
                  data-testid="answer-delete"
                  @click="handleDelete(answer.id)"
                >
                  {{ t('common.actions.delete') }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <div
          v-if="answerStore.answers.length === 0"
          class="py-8 text-center text-text-muted"
          data-testid="answer-list-empty"
        >
          {{ t('assessment.answers.noAnswers') }}
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
