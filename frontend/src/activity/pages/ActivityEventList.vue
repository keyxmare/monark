<script setup lang="ts">
import { onMounted } from 'vue'
import { RouterLink } from 'vue-router'

import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'
import { useActivityEventStore } from '@/activity/stores/activity-event'

const eventStore = useActivityEventStore()

onMounted(() => {
  eventStore.fetchAll()
})
</script>

<template>
  <DashboardLayout>
    <div data-testid="activity-event-list-page">
      <div class="mb-6">
        <h2 class="text-2xl font-bold text-text">
          Activity Events
        </h2>
      </div>

      <div
        v-if="eventStore.loading"
        class="py-8 text-center text-text-muted"
        data-testid="activity-event-list-loading"
      >
        Loading...
      </div>

      <div
        v-else-if="eventStore.error"
        class="rounded-lg bg-danger/10 p-4 text-danger"
        role="alert"
        data-testid="activity-event-list-error"
      >
        {{ eventStore.error }}
      </div>

      <div
        v-else
        class="overflow-hidden rounded-xl border border-border bg-surface"
      >
        <table
          class="w-full"
          data-testid="activity-event-list-table"
        >
          <thead>
            <tr class="border-b border-border bg-surface-muted">
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                Type
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                Entity Type
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                Entity ID
              </th>
              <th class="px-4 py-3 text-left text-sm font-medium text-text-muted">
                Occurred At
              </th>
              <th class="px-4 py-3 text-right text-sm font-medium text-text-muted">
                Actions
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="event in eventStore.events"
              :key="event.id"
              class="border-b border-border last:border-0"
              data-testid="activity-event-list-row"
            >
              <td class="px-4 py-3 text-sm text-text">
                <span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary">
                  {{ event.type }}
                </span>
              </td>
              <td class="px-4 py-3 text-sm text-text">
                {{ event.entityType }}
              </td>
              <td class="px-4 py-3 text-sm font-mono text-text-muted">
                {{ event.entityId }}
              </td>
              <td class="px-4 py-3 text-sm text-text">
                {{ new Date(event.occurredAt).toLocaleString() }}
              </td>
              <td class="px-4 py-3 text-right">
                <RouterLink
                  :to="{ name: 'activity-events-detail', params: { id: event.id } }"
                  class="text-sm text-primary hover:text-primary-dark"
                  data-testid="activity-event-view-link"
                >
                  View
                </RouterLink>
              </td>
            </tr>
          </tbody>
        </table>

        <div
          v-if="eventStore.events.length === 0"
          class="py-8 text-center text-text-muted"
          data-testid="activity-event-list-empty"
        >
          No activity events found.
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
