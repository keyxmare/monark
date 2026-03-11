<script setup lang="ts">
import { onMounted } from 'vue'
import { RouterLink, useRoute } from 'vue-router'

import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'
import { useActivityEventStore } from '@/activity/stores/activity-event'

const route = useRoute()
const eventStore = useActivityEventStore()

onMounted(() => {
  const id = route.params.id as string
  eventStore.fetchOne(id)
})
</script>

<template>
  <DashboardLayout>
    <div data-testid="activity-event-detail-page">
      <div class="mb-6">
        <RouterLink
          :to="{ name: 'activity-events-list' }"
          class="text-sm text-primary hover:text-primary-dark"
          data-testid="activity-event-detail-back"
        >
          &larr; Back to activity events
        </RouterLink>
      </div>

      <div
        v-if="eventStore.loading"
        class="py-8 text-center text-text-muted"
        data-testid="activity-event-detail-loading"
      >
        Loading...
      </div>

      <div
        v-else-if="eventStore.error"
        class="rounded-lg bg-danger/10 p-4 text-danger"
        role="alert"
        data-testid="activity-event-detail-error"
      >
        {{ eventStore.error }}
      </div>

      <div
        v-else-if="eventStore.selectedEvent"
        class="max-w-2xl rounded-xl border border-border bg-surface p-6"
        data-testid="activity-event-detail-card"
      >
        <h2 class="mb-6 text-2xl font-bold text-text">
          {{ eventStore.selectedEvent.type }}
        </h2>

        <dl class="space-y-4">
          <div>
            <dt class="text-sm font-medium text-text-muted">
              Type
            </dt>
            <dd
              class="mt-1 text-text"
              data-testid="activity-event-detail-type"
            >
              <span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary">
                {{ eventStore.selectedEvent.type }}
              </span>
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-text-muted">
              Entity Type
            </dt>
            <dd
              class="mt-1 text-text"
              data-testid="activity-event-detail-entity-type"
            >
              {{ eventStore.selectedEvent.entityType }}
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-text-muted">
              Entity ID
            </dt>
            <dd
              class="mt-1 font-mono text-text-muted"
              data-testid="activity-event-detail-entity-id"
            >
              {{ eventStore.selectedEvent.entityId }}
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-text-muted">
              User ID
            </dt>
            <dd
              class="mt-1 font-mono text-text-muted"
              data-testid="activity-event-detail-user-id"
            >
              {{ eventStore.selectedEvent.userId }}
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-text-muted">
              Occurred At
            </dt>
            <dd
              class="mt-1 text-text"
              data-testid="activity-event-detail-occurred-at"
            >
              {{ new Date(eventStore.selectedEvent.occurredAt).toLocaleString() }}
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-text-muted">
              Payload
            </dt>
            <dd
              class="mt-1"
              data-testid="activity-event-detail-payload"
            >
              <pre class="overflow-auto rounded-lg bg-surface-muted p-4 text-sm text-text">{{ JSON.stringify(eventStore.selectedEvent.payload, null, 2) }}</pre>
            </dd>
          </div>
        </dl>
      </div>
    </div>
  </DashboardLayout>
</template>
