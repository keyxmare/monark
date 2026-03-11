<script setup lang="ts">
import { onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, useRoute } from 'vue-router'

import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'
import { useTeamStore } from '@/identity/stores/team'

const route = useRoute()
const { t, d } = useI18n()
const teamStore = useTeamStore()

onMounted(() => {
  const id = route.params.id as string
  teamStore.fetchOne(id)
})
</script>

<template>
  <DashboardLayout>
    <div data-testid="team-detail-page">
      <div class="mb-6 flex items-center justify-between">
        <RouterLink
          :to="{ name: 'identity-teams-list' }"
          class="text-sm text-primary hover:text-primary-dark"
          data-testid="team-detail-back"
        >
          &larr; {{ t('common.backTo', { page: t('identity.teams.title').toLowerCase() }) }}
        </RouterLink>
        <RouterLink
          v-if="teamStore.selectedTeam"
          :to="{ name: 'identity-teams-edit', params: { id: teamStore.selectedTeam.id } }"
          class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark"
          data-testid="team-detail-edit"
        >
          {{ t('common.actions.edit') }}
        </RouterLink>
      </div>

      <div
        v-if="teamStore.loading"
        class="py-8 text-center text-text-muted"
        data-testid="team-detail-loading"
      >
        {{ t('common.actions.loading') }}
      </div>

      <div
        v-else-if="teamStore.error"
        class="rounded-lg bg-danger/10 p-4 text-danger"
        role="alert"
        data-testid="team-detail-error"
      >
        {{ teamStore.error }}
      </div>

      <div
        v-else-if="teamStore.selectedTeam"
        class="max-w-2xl rounded-xl border border-border bg-surface p-6"
        data-testid="team-detail-card"
      >
        <h2 class="mb-6 text-2xl font-bold text-text">
          {{ teamStore.selectedTeam.name }}
        </h2>

        <dl class="space-y-4">
          <div>
            <dt class="text-sm font-medium text-text-muted">
              {{ t('identity.teams.slug') }}
            </dt>
            <dd
              class="mt-1 text-text"
              data-testid="team-detail-slug"
            >
              {{ teamStore.selectedTeam.slug }}
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-text-muted">
              {{ t('identity.teams.description') }}
            </dt>
            <dd
              class="mt-1 text-text"
              data-testid="team-detail-description"
            >
              {{ teamStore.selectedTeam.description ?? t('common.noDescription') }}
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-text-muted">
              {{ t('identity.teams.members') }}
            </dt>
            <dd
              class="mt-1 text-text"
              data-testid="team-detail-member-count"
            >
              {{ t('identity.teams.memberCount', { count: teamStore.selectedTeam.memberCount }) }}
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-text-muted">
              {{ t('identity.users.createdAt') }}
            </dt>
            <dd
              class="mt-1 text-text"
              data-testid="team-detail-created-at"
            >
              {{ d(new Date(teamStore.selectedTeam.createdAt), 'short') }}
            </dd>
          </div>
        </dl>
      </div>
    </div>
  </DashboardLayout>
</template>
