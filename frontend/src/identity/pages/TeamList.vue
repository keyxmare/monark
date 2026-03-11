<script setup lang="ts">
import { onMounted } from 'vue'
import { RouterLink } from 'vue-router'

import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'
import { useTeamStore } from '@/identity/stores/team'

const teamStore = useTeamStore()

onMounted(() => {
  teamStore.fetchAll()
})

async function handleDelete(id: string) {
  await teamStore.remove(id)
}
</script>

<template>
  <DashboardLayout>
    <div data-testid="team-list-page">
      <div class="mb-6 flex items-center justify-between">
        <h2 class="text-2xl font-bold text-text">
          Teams
        </h2>
        <RouterLink
          :to="{ name: 'identity-teams-create' }"
          class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark"
          data-testid="team-create-link"
        >
          Create Team
        </RouterLink>
      </div>

      <div
        v-if="teamStore.loading"
        class="py-8 text-center text-text-muted"
        data-testid="team-list-loading"
      >
        Loading...
      </div>

      <div
        v-else-if="teamStore.error"
        class="rounded-lg bg-danger/10 p-4 text-danger"
        role="alert"
        data-testid="team-list-error"
      >
        {{ teamStore.error }}
      </div>

      <div
        v-else
        class="overflow-hidden rounded-xl border border-border bg-surface"
      >
        <table
          class="w-full"
          data-testid="team-list-table"
        >
          <thead>
            <tr class="border-b border-border bg-surface-muted">
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                Name
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                Slug
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                Members
              </th>
              <th class="px-4 py-3 text-right text-sm font-medium text-text-muted">
                Actions
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="team in teamStore.teams"
              :key="team.id"
              class="border-b border-border last:border-0"
              data-testid="team-list-row"
            >
              <td class="px-4 py-3 text-sm text-text">
                {{ team.name }}
              </td>
              <td class="px-4 py-3 text-sm text-text-muted">
                {{ team.slug }}
              </td>
              <td class="px-4 py-3 text-sm text-text">
                {{ team.memberCount }}
              </td>
              <td class="flex items-center justify-end gap-3 px-4 py-3">
                <RouterLink
                  :to="{ name: 'identity-teams-detail', params: { id: team.id } }"
                  class="text-sm text-primary hover:text-primary-dark"
                  data-testid="team-view-link"
                >
                  View
                </RouterLink>
                <RouterLink
                  :to="{ name: 'identity-teams-edit', params: { id: team.id } }"
                  class="text-sm text-primary hover:text-primary-dark"
                  data-testid="team-edit-link"
                >
                  Edit
                </RouterLink>
                <button
                  class="text-sm text-danger hover:text-danger/80"
                  data-testid="team-delete"
                  @click="handleDelete(team.id)"
                >
                  Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <div
          v-if="teamStore.teams.length === 0"
          class="py-8 text-center text-text-muted"
          data-testid="team-list-empty"
        >
          No teams found.
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
