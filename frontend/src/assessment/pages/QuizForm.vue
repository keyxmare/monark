<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, useRoute, useRouter } from 'vue-router'

import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'
import { useQuizStore } from '@/assessment/stores/quiz'
import type { QuizStatus, QuizType } from '@/assessment/types/quiz'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const quizStore = useQuizStore()

const quizId = computed(() => route.params.id as string | undefined)
const isEditMode = computed(() => !!quizId.value)

const title = ref('')
const slug = ref('')
const description = ref('')
const type = ref<QuizType>('quiz')
const status = ref<QuizStatus>('draft')
const startsAt = ref('')
const endsAt = ref('')
const timeLimit = ref<number | undefined>(undefined)
const submitting = ref(false)
const formError = ref('')

onMounted(async () => {
  if (isEditMode.value && quizId.value) {
    await quizStore.fetchOne(quizId.value)
    if (quizStore.selectedQuiz) {
      title.value = quizStore.selectedQuiz.title
      slug.value = quizStore.selectedQuiz.slug
      description.value = quizStore.selectedQuiz.description
      type.value = quizStore.selectedQuiz.type
      status.value = quizStore.selectedQuiz.status
      startsAt.value = quizStore.selectedQuiz.startsAt?.slice(0, 16) ?? ''
      endsAt.value = quizStore.selectedQuiz.endsAt?.slice(0, 16) ?? ''
      timeLimit.value = quizStore.selectedQuiz.timeLimit ?? undefined
    }
  }
})

async function handleSubmit() {
  formError.value = ''
  submitting.value = true

  try {
    if (isEditMode.value && quizId.value) {
      await quizStore.update(quizId.value, {
        title: title.value,
        slug: slug.value,
        description: description.value,
        type: type.value,
        status: status.value,
        startsAt: startsAt.value || undefined,
        endsAt: endsAt.value || undefined,
        timeLimit: timeLimit.value,
      })
      router.push({ name: 'assessment-quizzes-detail', params: { id: quizId.value } })
    } else {
      const quiz = await quizStore.create({
        title: title.value,
        slug: slug.value,
        description: description.value,
        type: type.value,
        status: status.value,
        startsAt: startsAt.value || undefined,
        endsAt: endsAt.value || undefined,
        timeLimit: timeLimit.value,
      })
      router.push({ name: 'assessment-quizzes-detail', params: { id: quiz.id } })
    }
  } catch {
    formError.value = isEditMode.value
      ? t('assessment.quizzes.updateFailed')
      : t('assessment.quizzes.createFailed')
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <DashboardLayout>
    <div data-testid="quiz-form-page">
      <div class="mb-6">
        <RouterLink
          :to="{ name: 'assessment-quizzes-list' }"
          class="text-sm text-primary hover:text-primary-dark"
          data-testid="quiz-form-back"
        >
          &larr; {{ t('common.backTo', { page: t('assessment.quizzes.title').toLowerCase() }) }}
        </RouterLink>
      </div>

      <div class="max-w-lg rounded-xl border border-border bg-surface p-6">
        <h2 class="mb-6 text-2xl font-bold text-text">
          {{ isEditMode ? t('assessment.quizzes.editQuiz') : t('assessment.quizzes.createQuiz') }}
        </h2>

        <form
          data-testid="quiz-form"
          @submit.prevent="handleSubmit"
        >
          <div
            v-if="formError"
            class="mb-4 rounded-lg bg-danger/10 p-3 text-sm text-danger"
            role="alert"
            data-testid="quiz-form-error"
          >
            {{ formError }}
          </div>

          <div class="mb-4">
            <label
              for="title"
              class="mb-1 block text-sm font-medium text-text"
            >{{ t('assessment.quizzes.quizTitle') }}</label>
            <input
              id="title"
              v-model="title"
              type="text"
              required
              class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
              data-testid="quiz-form-title"
            >
          </div>

          <div class="mb-4">
            <label
              for="slug"
              class="mb-1 block text-sm font-medium text-text"
            >{{ t('assessment.quizzes.slug') }}</label>
            <input
              id="slug"
              v-model="slug"
              type="text"
              required
              pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
              class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
              data-testid="quiz-form-slug"
            >
          </div>

          <div class="mb-4">
            <label
              for="description"
              class="mb-1 block text-sm font-medium text-text"
            >{{ t('assessment.quizzes.description') }}</label>
            <textarea
              id="description"
              v-model="description"
              rows="3"
              required
              class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
              data-testid="quiz-form-description"
            />
          </div>

          <div class="mb-4 flex gap-4">
            <div class="flex-1">
              <label
                for="type"
                class="mb-1 block text-sm font-medium text-text"
              >{{ t('assessment.quizzes.type') }}</label>
              <select
                id="type"
                v-model="type"
                class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
                data-testid="quiz-form-type"
              >
                <option value="quiz">
                  {{ t('assessment.quizzes.typeQuiz') }}
                </option>
                <option value="survey">
                  {{ t('assessment.quizzes.typeSurvey') }}
                </option>
              </select>
            </div>
            <div class="flex-1">
              <label
                for="status"
                class="mb-1 block text-sm font-medium text-text"
              >{{ t('assessment.quizzes.status') }}</label>
              <select
                id="status"
                v-model="status"
                class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
                data-testid="quiz-form-status"
              >
                <option value="draft">
                  {{ t('assessment.quizzes.statusDraft') }}
                </option>
                <option value="published">
                  {{ t('assessment.quizzes.statusPublished') }}
                </option>
                <option value="archived">
                  {{ t('assessment.quizzes.statusArchived') }}
                </option>
              </select>
            </div>
          </div>

          <div class="mb-4 flex gap-4">
            <div class="flex-1">
              <label
                for="startsAt"
                class="mb-1 block text-sm font-medium text-text"
              >{{ t('assessment.quizzes.startsAt') }}</label>
              <input
                id="startsAt"
                v-model="startsAt"
                type="datetime-local"
                class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
                data-testid="quiz-form-starts-at"
              >
            </div>
            <div class="flex-1">
              <label
                for="endsAt"
                class="mb-1 block text-sm font-medium text-text"
              >{{ t('assessment.quizzes.endsAt') }}</label>
              <input
                id="endsAt"
                v-model="endsAt"
                type="datetime-local"
                class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
                data-testid="quiz-form-ends-at"
              >
            </div>
          </div>

          <div class="mb-6">
            <label
              for="timeLimit"
              class="mb-1 block text-sm font-medium text-text"
            >{{ t('assessment.quizzes.timeLimit') }}</label>
            <input
              id="timeLimit"
              v-model.number="timeLimit"
              type="number"
              min="0"
              class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
              data-testid="quiz-form-time-limit"
            >
          </div>

          <button
            type="submit"
            :disabled="submitting"
            class="w-full rounded-lg bg-primary px-4 py-2.5 font-medium text-white transition-colors hover:bg-primary-dark disabled:opacity-50"
            data-testid="quiz-form-submit"
          >
            {{ submitting ? t('common.saving') : (isEditMode ? t('assessment.quizzes.updateQuiz') : t('assessment.quizzes.createQuiz')) }}
          </button>
        </form>
      </div>
    </div>
  </DashboardLayout>
</template>
