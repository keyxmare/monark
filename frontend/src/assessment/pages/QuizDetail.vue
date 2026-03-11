<script setup lang="ts">
import { onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, useRoute } from 'vue-router'

import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'
import { useQuizStore } from '@/assessment/stores/quiz'
import { useQuestionStore } from '@/assessment/stores/question'

const route = useRoute()
const { t } = useI18n()
const quizStore = useQuizStore()
const questionStore = useQuestionStore()

onMounted(() => {
  const id = route.params.id as string
  quizStore.fetchOne(id)
  questionStore.fetchAll(1, 50, id)
})

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
    <div data-testid="quiz-detail-page">
      <div class="mb-6 flex items-center justify-between">
        <RouterLink
          :to="{ name: 'assessment-quizzes-list' }"
          class="text-sm text-primary hover:text-primary-dark"
          data-testid="quiz-detail-back"
        >
          &larr; {{ t('common.backTo', { page: t('assessment.quizzes.title').toLowerCase() }) }}
        </RouterLink>
        <RouterLink
          v-if="quizStore.selectedQuiz"
          :to="{ name: 'assessment-quizzes-edit', params: { id: quizStore.selectedQuiz.id } }"
          class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark"
          data-testid="quiz-detail-edit"
        >
          {{ t('common.actions.edit') }}
        </RouterLink>
      </div>

      <div
        v-if="quizStore.loading"
        class="py-8 text-center text-text-muted"
        data-testid="quiz-detail-loading"
      >
        {{ t('common.actions.loading') }}
      </div>

      <div
        v-else-if="quizStore.error"
        class="rounded-lg bg-danger/10 p-4 text-danger"
        role="alert"
        data-testid="quiz-detail-error"
      >
        {{ quizStore.error }}
      </div>

      <div
        v-else-if="quizStore.selectedQuiz"
        class="space-y-6"
      >
        <div
          class="max-w-2xl rounded-xl border border-border bg-surface p-6"
          data-testid="quiz-detail-card"
        >
          <h2 class="mb-6 text-2xl font-bold text-text">
            {{ quizStore.selectedQuiz.title }}
          </h2>

          <dl class="space-y-4">
            <div>
              <dt class="text-sm font-medium text-text-muted">
                {{ t('assessment.quizzes.slug') }}
              </dt>
              <dd
                class="mt-1 text-text"
                data-testid="quiz-detail-slug"
              >
                {{ quizStore.selectedQuiz.slug }}
              </dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-text-muted">
                {{ t('assessment.quizzes.description') }}
              </dt>
              <dd
                class="mt-1 text-text"
                data-testid="quiz-detail-description"
              >
                {{ quizStore.selectedQuiz.description }}
              </dd>
            </div>
            <div class="flex gap-8">
              <div>
                <dt class="text-sm font-medium text-text-muted">
                  {{ t('assessment.quizzes.type') }}
                </dt>
                <dd
                  class="mt-1 text-text"
                  data-testid="quiz-detail-type"
                >
                  {{ quizStore.selectedQuiz.type }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-text-muted">
                  {{ t('assessment.quizzes.status') }}
                </dt>
                <dd
                  class="mt-1 text-text"
                  data-testid="quiz-detail-status"
                >
                  {{ quizStore.selectedQuiz.status }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-text-muted">
                  {{ t('assessment.quizzes.timeLimit') }}
                </dt>
                <dd
                  class="mt-1 text-text"
                  data-testid="quiz-detail-time-limit"
                >
                  {{ quizStore.selectedQuiz.timeLimit ? `${quizStore.selectedQuiz.timeLimit} min` : t('assessment.quizzes.noLimit') }}
                </dd>
              </div>
            </div>
            <div class="flex gap-8">
              <div>
                <dt class="text-sm font-medium text-text-muted">
                  {{ t('assessment.quizzes.startsAt') }}
                </dt>
                <dd
                  class="mt-1 text-text"
                  data-testid="quiz-detail-starts-at"
                >
                  {{ quizStore.selectedQuiz.startsAt ? new Date(quizStore.selectedQuiz.startsAt).toLocaleDateString() : t('common.notSet') }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-text-muted">
                  {{ t('assessment.quizzes.endsAt') }}
                </dt>
                <dd
                  class="mt-1 text-text"
                  data-testid="quiz-detail-ends-at"
                >
                  {{ quizStore.selectedQuiz.endsAt ? new Date(quizStore.selectedQuiz.endsAt).toLocaleDateString() : t('common.notSet') }}
                </dd>
              </div>
            </div>
            <div>
              <dt class="text-sm font-medium text-text-muted">
                {{ t('common.createdAt') }}
              </dt>
              <dd
                class="mt-1 text-text"
                data-testid="quiz-detail-created-at"
              >
                {{ new Date(quizStore.selectedQuiz.createdAt).toLocaleDateString() }}
              </dd>
            </div>
          </dl>
        </div>

        <div class="rounded-xl border border-border bg-surface">
          <div class="flex items-center justify-between border-b border-border px-4 py-3">
            <h3 class="text-lg font-semibold text-text">
              {{ t('assessment.quizzes.questionsCount', { count: questionStore.total }) }}
            </h3>
            <RouterLink
              :to="{ name: 'assessment-questions-create' }"
              class="rounded-lg bg-primary px-3 py-1.5 text-sm font-medium text-white transition-colors hover:bg-primary-dark"
              data-testid="quiz-add-question"
            >
              {{ t('assessment.quizzes.addQuestion') }}
            </RouterLink>
          </div>
          <table
            class="w-full"
            data-testid="quiz-questions-table"
          >
            <thead>
              <tr class="border-b border-border bg-surface-muted">
                <th class="px-4 py-2 text-left text-sm font-medium text-text-muted">
                  #
                </th>
                <th class="px-4 py-2 text-left text-sm font-medium text-text-muted">
                  {{ t('assessment.questions.type') }}
                </th>
                <th class="px-4 py-2 text-left text-sm font-medium text-text-muted">
                  {{ t('assessment.questions.content') }}
                </th>
                <th class="px-4 py-2 text-left text-sm font-medium text-text-muted">
                  {{ t('assessment.questions.level') }}
                </th>
                <th class="px-4 py-2 text-left text-sm font-medium text-text-muted">
                  {{ t('assessment.questions.score') }}
                </th>
                <th class="px-4 py-2 text-right text-sm font-medium text-text-muted">
                  {{ t('common.table.actions') }}
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="question in questionStore.questions"
                :key="question.id"
                class="border-b border-border last:border-0"
                data-testid="quiz-question-row"
              >
                <td class="px-4 py-2 text-sm text-text-muted">
                  {{ question.position }}
                </td>
                <td class="px-4 py-2 text-sm">
                  <span class="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary">
                    {{ question.type }}
                  </span>
                </td>
                <td class="max-w-xs truncate px-4 py-2 text-sm text-text">
                  {{ question.content }}
                </td>
                <td class="px-4 py-2 text-sm">
                  <span
                    :class="levelColor(question.level)"
                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                  >
                    {{ question.level }}
                  </span>
                </td>
                <td class="px-4 py-2 text-sm text-text">
                  {{ question.score }}
                </td>
                <td class="flex items-center justify-end gap-2 px-4 py-2">
                  <RouterLink
                    :to="{ name: 'assessment-questions-detail', params: { id: question.id } }"
                    class="text-sm text-primary hover:text-primary-dark"
                    data-testid="question-view-link"
                  >
                    {{ t('common.actions.view') }}
                  </RouterLink>
                </td>
              </tr>
            </tbody>
          </table>
          <div
            v-if="questionStore.questions.length === 0"
            class="py-6 text-center text-text-muted"
            data-testid="quiz-questions-empty"
          >
            {{ t('assessment.quizzes.noQuestions') }}
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
