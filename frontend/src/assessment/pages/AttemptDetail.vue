<script setup lang="ts">
import { onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, useRoute } from 'vue-router'

import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'
import { useAttemptStore } from '@/assessment/stores/attempt'

const route = useRoute()
const { t } = useI18n()
const attemptStore = useAttemptStore()

onMounted(() => {
  const id = route.params.id as string
  attemptStore.fetchOne(id)
})
</script>

<template>
  <DashboardLayout>
    <div data-testid="attempt-detail-page">
      <div class="mb-6">
        <RouterLink
          :to="{ name: 'assessment-attempts-list' }"
          class="text-sm text-primary hover:text-primary-dark"
          data-testid="attempt-detail-back"
        >
          &larr; {{ t('common.backTo', { page: t('assessment.attempts.title').toLowerCase() }) }}
        </RouterLink>
      </div>

      <div
        v-if="attemptStore.loading"
        class="py-8 text-center text-text-muted"
        data-testid="attempt-detail-loading"
      >
        {{ t('common.actions.loading') }}
      </div>

      <div
        v-else-if="attemptStore.error"
        class="rounded-lg bg-danger/10 p-4 text-danger"
        role="alert"
        data-testid="attempt-detail-error"
      >
        {{ attemptStore.error }}
      </div>

      <div
        v-else-if="attemptStore.selectedAttempt"
        class="max-w-2xl rounded-xl border border-border bg-surface p-6"
        data-testid="attempt-detail-card"
      >
        <h2 class="mb-6 text-2xl font-bold text-text">
          {{ t('assessment.attempts.attemptDetails') }}
        </h2>

        <dl class="space-y-4">
          <div>
            <dt class="text-sm font-medium text-text-muted">
              {{ t('assessment.attempts.quiz') }}
            </dt>
            <dd
              class="mt-1 text-text"
              data-testid="attempt-detail-quiz"
            >
              {{ attemptStore.selectedAttempt.quizId }}
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-text-muted">
              {{ t('assessment.attempts.user') }}
            </dt>
            <dd
              class="mt-1 text-text"
              data-testid="attempt-detail-user"
            >
              {{ attemptStore.selectedAttempt.userId }}
            </dd>
          </div>
          <div class="flex gap-8">
            <div>
              <dt class="text-sm font-medium text-text-muted">
                {{ t('assessment.attempts.score') }}
              </dt>
              <dd
                class="mt-1 text-text"
                data-testid="attempt-detail-score"
              >
                {{ attemptStore.selectedAttempt.score }}
              </dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-text-muted">
                {{ t('assessment.attempts.status') }}
              </dt>
              <dd
                class="mt-1 text-text"
                data-testid="attempt-detail-status"
              >
                {{ attemptStore.selectedAttempt.status }}
              </dd>
            </div>
          </div>
          <div class="flex gap-8">
            <div>
              <dt class="text-sm font-medium text-text-muted">
                {{ t('assessment.attempts.startedAt') }}
              </dt>
              <dd
                class="mt-1 text-text"
                data-testid="attempt-detail-started-at"
              >
                {{ new Date(attemptStore.selectedAttempt.startedAt).toLocaleString() }}
              </dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-text-muted">
                {{ t('assessment.attempts.finishedAt') }}
              </dt>
              <dd
                class="mt-1 text-text"
                data-testid="attempt-detail-finished-at"
              >
                {{ attemptStore.selectedAttempt.finishedAt ? new Date(attemptStore.selectedAttempt.finishedAt).toLocaleString() : t('assessment.attempts.notFinished') }}
              </dd>
            </div>
          </div>
          <div>
            <dt class="text-sm font-medium text-text-muted">
              {{ t('common.createdAt') }}
            </dt>
            <dd
              class="mt-1 text-text"
              data-testid="attempt-detail-created-at"
            >
              {{ new Date(attemptStore.selectedAttempt.createdAt).toLocaleDateString() }}
            </dd>
          </div>
        </dl>
      </div>
    </div>
  </DashboardLayout>
</template>
