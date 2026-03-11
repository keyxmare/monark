<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, useRoute, useRouter } from 'vue-router'

import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'
import { useQuestionStore } from '@/assessment/stores/question'
import { useQuizStore } from '@/assessment/stores/quiz'
import type { QuestionLevel, QuestionType } from '@/assessment/types/question'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const questionStore = useQuestionStore()
const quizStore = useQuizStore()

const questionId = computed(() => route.params.id as string | undefined)
const isEditMode = computed(() => !!questionId.value)

const type = ref<QuestionType>('single_choice')
const content = ref('')
const level = ref<QuestionLevel>('medium')
const score = ref(1)
const position = ref(0)
const quizId = ref('')
const submitting = ref(false)
const formError = ref('')

onMounted(async () => {
  await quizStore.fetchAll(1, 100)

  if (isEditMode.value && questionId.value) {
    await questionStore.fetchOne(questionId.value)
    if (questionStore.selectedQuestion) {
      type.value = questionStore.selectedQuestion.type
      content.value = questionStore.selectedQuestion.content
      level.value = questionStore.selectedQuestion.level
      score.value = questionStore.selectedQuestion.score
      position.value = questionStore.selectedQuestion.position
      quizId.value = questionStore.selectedQuestion.quizId
    }
  }
})

async function handleSubmit() {
  formError.value = ''
  submitting.value = true

  try {
    if (isEditMode.value && questionId.value) {
      await questionStore.update(questionId.value, {
        type: type.value,
        content: content.value,
        level: level.value,
        score: score.value,
        position: position.value,
      })
      router.push({ name: 'assessment-questions-detail', params: { id: questionId.value } })
    } else {
      const question = await questionStore.create({
        type: type.value,
        content: content.value,
        level: level.value,
        score: score.value,
        position: position.value,
        quizId: quizId.value,
      })
      router.push({ name: 'assessment-questions-detail', params: { id: question.id } })
    }
  } catch {
    formError.value = isEditMode.value
      ? t('assessment.questions.updateFailed')
      : t('assessment.questions.createFailed')
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <DashboardLayout>
    <div data-testid="question-form-page">
      <div class="mb-6">
        <RouterLink
          :to="{ name: 'assessment-questions-list' }"
          class="text-sm text-primary hover:text-primary-dark"
          data-testid="question-form-back"
        >
          &larr; {{ t('common.backTo', { page: t('assessment.questions.title').toLowerCase() }) }}
        </RouterLink>
      </div>

      <div class="max-w-lg rounded-xl border border-border bg-surface p-6">
        <h2 class="mb-6 text-2xl font-bold text-text">
          {{ isEditMode ? t('assessment.questions.editQuestion') : t('assessment.questions.createQuestion') }}
        </h2>

        <form
          data-testid="question-form"
          @submit.prevent="handleSubmit"
        >
          <div
            v-if="formError"
            class="mb-4 rounded-lg bg-danger/10 p-3 text-sm text-danger"
            role="alert"
            data-testid="question-form-error"
          >
            {{ formError }}
          </div>

          <div
            v-if="!isEditMode"
            class="mb-4"
          >
            <label
              for="quizId"
              class="mb-1 block text-sm font-medium text-text"
            >{{ t('assessment.questions.quiz') }}</label>
            <select
              id="quizId"
              v-model="quizId"
              required
              class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
              data-testid="question-form-quiz"
            >
              <option
                value=""
                disabled
              >
                {{ t('assessment.questions.selectQuiz') }}
              </option>
              <option
                v-for="quiz in quizStore.quizzes"
                :key="quiz.id"
                :value="quiz.id"
              >
                {{ quiz.title }}
              </option>
            </select>
          </div>

          <div class="mb-4 flex gap-4">
            <div class="flex-1">
              <label
                for="type"
                class="mb-1 block text-sm font-medium text-text"
              >{{ t('assessment.questions.type') }}</label>
              <select
                id="type"
                v-model="type"
                class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
                data-testid="question-form-type"
              >
                <option value="single_choice">
                  {{ t('assessment.questions.typeSingleChoice') }}
                </option>
                <option value="multiple_choice">
                  {{ t('assessment.questions.typeMultipleChoice') }}
                </option>
                <option value="text">
                  {{ t('assessment.questions.typeText') }}
                </option>
                <option value="code">
                  {{ t('assessment.questions.typeCode') }}
                </option>
              </select>
            </div>
            <div class="flex-1">
              <label
                for="level"
                class="mb-1 block text-sm font-medium text-text"
              >{{ t('assessment.questions.level') }}</label>
              <select
                id="level"
                v-model="level"
                class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
                data-testid="question-form-level"
              >
                <option value="easy">
                  {{ t('assessment.questions.levelEasy') }}
                </option>
                <option value="medium">
                  {{ t('assessment.questions.levelMedium') }}
                </option>
                <option value="hard">
                  {{ t('assessment.questions.levelHard') }}
                </option>
              </select>
            </div>
          </div>

          <div class="mb-4">
            <label
              for="content"
              class="mb-1 block text-sm font-medium text-text"
            >{{ t('assessment.questions.content') }}</label>
            <textarea
              id="content"
              v-model="content"
              rows="4"
              required
              class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
              data-testid="question-form-content"
            />
          </div>

          <div class="mb-6 flex gap-4">
            <div class="flex-1">
              <label
                for="score"
                class="mb-1 block text-sm font-medium text-text"
              >{{ t('assessment.questions.score') }}</label>
              <input
                id="score"
                v-model.number="score"
                type="number"
                min="0"
                required
                class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
                data-testid="question-form-score"
              >
            </div>
            <div class="flex-1">
              <label
                for="position"
                class="mb-1 block text-sm font-medium text-text"
              >{{ t('assessment.questions.position') }}</label>
              <input
                id="position"
                v-model.number="position"
                type="number"
                min="0"
                required
                class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
                data-testid="question-form-position"
              >
            </div>
          </div>

          <button
            type="submit"
            :disabled="submitting"
            class="w-full rounded-lg bg-primary px-4 py-2.5 font-medium text-white transition-colors hover:bg-primary-dark disabled:opacity-50"
            data-testid="question-form-submit"
          >
            {{ submitting ? t('common.saving') : (isEditMode ? t('assessment.questions.updateQuestion') : t('assessment.questions.createQuestion')) }}
          </button>
        </form>
      </div>
    </div>
  </DashboardLayout>
</template>
