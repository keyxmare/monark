<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'

import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'
import { useAnswerStore } from '@/assessment/stores/answer'
import { useQuestionStore } from '@/assessment/stores/question'

const route = useRoute()
const router = useRouter()
const answerStore = useAnswerStore()
const questionStore = useQuestionStore()

const answerId = computed(() => route.params.id as string | undefined)
const isEditMode = computed(() => !!answerId.value)

const content = ref('')
const isCorrect = ref(false)
const position = ref(0)
const questionId = ref('')
const submitting = ref(false)
const error = ref('')

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
  error.value = ''
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
    error.value = isEditMode.value ? 'Failed to update answer' : 'Failed to create answer'
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
          &larr; Back to answers
        </RouterLink>
      </div>

      <div class="max-w-lg rounded-xl border border-border bg-surface p-6">
        <h2 class="mb-6 text-2xl font-bold text-text">
          {{ isEditMode ? 'Edit Answer' : 'Create Answer' }}
        </h2>

        <form
          data-testid="answer-form"
          @submit.prevent="handleSubmit"
        >
          <div
            v-if="error"
            class="mb-4 rounded-lg bg-danger/10 p-3 text-sm text-danger"
            role="alert"
            data-testid="answer-form-error"
          >
            {{ error }}
          </div>

          <div
            v-if="!isEditMode"
            class="mb-4"
          >
            <label
              for="questionId"
              class="mb-1 block text-sm font-medium text-text"
            >Question</label>
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
                Select a question
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
            >Content</label>
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
            >Correct answer</label>
          </div>

          <div class="mb-6">
            <label
              for="position"
              class="mb-1 block text-sm font-medium text-text"
            >Position</label>
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
            {{ submitting ? 'Saving...' : (isEditMode ? 'Update Answer' : 'Create Answer') }}
          </button>
        </form>
      </div>
    </div>
  </DashboardLayout>
</template>
