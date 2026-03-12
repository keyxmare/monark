<script setup lang="ts">
import { onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, useRoute } from 'vue-router'

import { useAnswerStore } from '@/assessment/stores/answer'
import { useQuestionStore } from '@/assessment/stores/question'
import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'

const route = useRoute()
const { t } = useI18n()
const questionStore = useQuestionStore()
const answerStore = useAnswerStore()

onMounted(() => {
  const id = route.params.id as string
  questionStore.fetchOne(id)
  answerStore.fetchAll(1, 50, id)
})
</script>

<template>
  <DashboardLayout>
    <div data-testid="question-detail-page">
      <div class="mb-6 flex items-center justify-between">
        <RouterLink
          :to="{ name: 'assessment-questions-list' }"
          class="text-sm text-primary hover:text-primary-dark"
          data-testid="question-detail-back"
        >
          &larr; {{ t('common.backTo', { page: t('assessment.questions.title').toLowerCase() }) }}
        </RouterLink>
        <RouterLink
          v-if="questionStore.selectedQuestion"
          :to="{ name: 'assessment-questions-edit', params: { id: questionStore.selectedQuestion.id } }"
          class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark"
          data-testid="question-detail-edit"
        >
          {{ t('common.actions.edit') }}
        </RouterLink>
      </div>

      <div
        v-if="questionStore.loading"
        class="py-8 text-center text-text-muted"
        data-testid="question-detail-loading"
      >
        {{ t('common.actions.loading') }}
      </div>

      <div
        v-else-if="questionStore.error"
        class="rounded-lg bg-danger/10 p-4 text-danger"
        role="alert"
        data-testid="question-detail-error"
      >
        {{ questionStore.error }}
      </div>

      <div
        v-else-if="questionStore.selectedQuestion"
        class="space-y-6"
      >
        <div
          class="max-w-2xl rounded-xl border border-border bg-surface p-6"
          data-testid="question-detail-card"
        >
          <h2 class="mb-6 text-2xl font-bold text-text">
            {{ t('assessment.questions.questionNumber', { position: questionStore.selectedQuestion.position }) }}
          </h2>

          <dl class="space-y-4">
            <div>
              <dt class="text-sm font-medium text-text-muted">
                {{ t('assessment.questions.content') }}
              </dt>
              <dd
                class="mt-1 text-text"
                data-testid="question-detail-content"
              >
                {{ questionStore.selectedQuestion.content }}
              </dd>
            </div>
            <div class="flex gap-8">
              <div>
                <dt class="text-sm font-medium text-text-muted">
                  {{ t('assessment.questions.type') }}
                </dt>
                <dd
                  class="mt-1 text-text"
                  data-testid="question-detail-type"
                >
                  {{ questionStore.selectedQuestion.type }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-text-muted">
                  {{ t('assessment.questions.level') }}
                </dt>
                <dd
                  class="mt-1 text-text"
                  data-testid="question-detail-level"
                >
                  {{ questionStore.selectedQuestion.level }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-text-muted">
                  {{ t('assessment.questions.score') }}
                </dt>
                <dd
                  class="mt-1 text-text"
                  data-testid="question-detail-score"
                >
                  {{ questionStore.selectedQuestion.score }}
                </dd>
              </div>
            </div>
          </dl>
        </div>

        <div class="rounded-xl border border-border bg-surface">
          <div class="flex items-center justify-between border-b border-border px-4 py-3">
            <h3 class="text-lg font-semibold text-text">
              {{ t('assessment.answers.answersCount', { count: answerStore.total }) }}
            </h3>
            <RouterLink
              :to="{ name: 'assessment-answers-create' }"
              class="rounded-lg bg-primary px-3 py-1.5 text-sm font-medium text-white transition-colors hover:bg-primary-dark"
              data-testid="question-add-answer"
            >
              {{ t('assessment.answers.addAnswer') }}
            </RouterLink>
          </div>
          <table
            class="w-full"
            data-testid="question-answers-table"
          >
            <thead>
              <tr class="border-b border-border bg-surface-muted">
                <th class="px-4 py-2 text-left text-sm font-medium text-text-muted">
                  #
                </th>
                <th class="px-4 py-2 text-left text-sm font-medium text-text-muted">
                  {{ t('assessment.answers.content') }}
                </th>
                <th class="px-4 py-2 text-left text-sm font-medium text-text-muted">
                  {{ t('assessment.answers.correct') }}
                </th>
                <th class="px-4 py-2 text-right text-sm font-medium text-text-muted">
                  {{ t('common.table.actions') }}
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="answer in answerStore.answers"
                :key="answer.id"
                class="border-b border-border last:border-0"
                data-testid="answer-row"
              >
                <td class="px-4 py-2 text-sm text-text-muted">
                  {{ answer.position }}
                </td>
                <td class="px-4 py-2 text-sm text-text">
                  {{ answer.content }}
                </td>
                <td class="px-4 py-2 text-sm">
                  <span
                    :class="answer.isCorrect ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger'"
                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                    data-testid="answer-correct-badge"
                  >
                    {{ answer.isCorrect ? t('common.confirm.yes') : t('common.confirm.no') }}
                  </span>
                </td>
                <td class="flex items-center justify-end gap-2 px-4 py-2">
                  <RouterLink
                    :to="{ name: 'assessment-answers-edit', params: { id: answer.id } }"
                    class="text-sm text-primary hover:text-primary-dark"
                    data-testid="answer-edit-link"
                  >
                    {{ t('common.actions.edit') }}
                  </RouterLink>
                </td>
              </tr>
            </tbody>
          </table>
          <div
            v-if="answerStore.answers.length === 0"
            class="py-6 text-center text-text-muted"
            data-testid="question-answers-empty"
          >
            {{ t('assessment.answers.noAnswersYet') }}
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
