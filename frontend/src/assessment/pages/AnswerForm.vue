<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, useRoute, useRouter } from 'vue-router'

import { useAnswerStore } from '@/assessment/stores/answer'
import { useQuestionStore } from '@/assessment/stores/question'
import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const answerStore = useAnswerStore()
const questionStore = useQuestionStore()

const answerId = computed(() => route.params.id as string | undefined)
const isEditMode = computed(() => !!answerId.value)

const content = ref('')
const isCorrect = ref(false)
const position = ref(0)
const questionId = ref('')
const submitting = ref(false)
const formError = ref('')

onMounted(async () => {
  await questionStore.fetchAll(1, 100)

  if (isEditMode.value && answerId.value) {
    await answerStore.fetchOne(answerId.value)
    if (answerStore.selectedAnswer) {
      content.value = answerStore.selectedAnswer.content
      isCorrect.value = answerStore.selectedAnswer.isCorrect
      position.value = answerStore.selectedAnswer.position
      questionId.value = answerStore.selectedAnswer.questionId
    }
  }
})

async function handleSubmit() {
  formError.value = ''
  submitting.value = true

  try {
    if (isEditMode.value && answerId.value) {
      await answerStore.update(answerId.value, {
        content: content.value,
        isCorrect: isCorrect.value,
        position: position.value,
      })
      router.push({ name: 'assessment-answers-list' })
    } else {
      await answerStore.create({
        content: content.value,
        isCorrect: isCorrect.value,
        position: position.value,
        questionId: questionId.value,
      })
      router.push({ name: 'assessment-answers-list' })
    }
  } catch {
    formError.value = isEditMode.value
      ? t('assessment.answers.updateFailed')
      : t('assessment.answers.createFailed')
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <DashboardLayout>
    <div data-testid="answer-form-page">
      <div class="mb-6">
        <RouterLink
          :to="{ name: 'assessment-answers-list' }"
          class="text-sm text-primary hover:text-primary-dark"
          data-testid="answer-form-back"
        >
          &larr; {{ t('common.backTo', { page: t('assessment.answers.title').toLowerCase() }) }}
        </RouterLink>
      </div>

      <div class="max-w-lg rounded-xl border border-border bg-surface p-6">
        <h2 class="mb-6 text-2xl font-bold text-text">
          {{ isEditMode ? t('assessment.answers.editAnswer') : t('assessment.answers.createAnswer') }}
        </h2>

        <form
          data-testid="answer-form"
          @submit.prevent="handleSubmit"
        >
          <div
            v-if="formError"
            class="mb-4 rounded-lg bg-danger/10 p-3 text-sm text-danger"
            role="alert"
            data-testid="answer-form-error"
          >
            {{ formError }}
          </div>

          <div
            v-if="!isEditMode"
            class="mb-4"
          >
            <label
              for="questionId"
              class="mb-1 block text-sm font-medium text-text"
            >{{ t('assessment.answers.question') }}</label>
            <select
              id="questionId"
              v-model="questionId"
              required
              class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
              data-testid="answer-form-question"
            >
              <option
                value=""
                disabled
              >
                {{ t('assessment.answers.selectQuestion') }}
              </option>
              <option
                v-for="question in questionStore.questions"
                :key="question.id"
                :value="question.id"
              >
                #{{ question.position }} - {{ question.content.substring(0, 60) }}
              </option>
            </select>
          </div>

          <div class="mb-4">
            <label
              for="content"
              class="mb-1 block text-sm font-medium text-text"
            >{{ t('assessment.answers.content') }}</label>
            <textarea
              id="content"
              v-model="content"
              rows="3"
              required
              class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
              data-testid="answer-form-content"
            />
          </div>

          <div class="mb-4 flex items-center gap-2">
            <input
              id="isCorrect"
              v-model="isCorrect"
              type="checkbox"
              class="rounded border-border text-primary focus:ring-primary/20"
              data-testid="answer-form-is-correct"
            >
            <label
              for="isCorrect"
              class="text-sm font-medium text-text"
            >{{ t('assessment.answers.correctAnswer') }}</label>
          </div>

          <div class="mb-6">
            <label
              for="position"
              class="mb-1 block text-sm font-medium text-text"
            >{{ t('assessment.answers.position') }}</label>
            <input
              id="position"
              v-model.number="position"
              type="number"
              min="0"
              required
              class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
              data-testid="answer-form-position"
            >
          </div>

          <button
            type="submit"
            :disabled="submitting"
            class="w-full rounded-lg bg-primary px-4 py-2.5 font-medium text-white transition-colors hover:bg-primary-dark disabled:opacity-50"
            data-testid="answer-form-submit"
          >
            {{ submitting ? t('common.saving') : (isEditMode ? t('assessment.answers.updateAnswer') : t('assessment.answers.createAnswer')) }}
          </button>
        </form>
      </div>
    </div>
  </DashboardLayout>
</template>
