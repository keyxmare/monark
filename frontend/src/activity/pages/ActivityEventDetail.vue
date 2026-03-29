<script setup lang="ts">
import { onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink, useRoute } from 'vue-router';

import { useActivityEventStore } from '@/activity/stores/activity-event';
import DashboardLayout from '@/shared/layouts/DashboardLayout.vue';

const route = useRoute();
const { d, t } = useI18n();
const eventStore = useActivityEventStore();

onMounted(() => {
  const id = route.params.id as string;
  eventStore.fetchOne(id);
});
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
          &larr; {{ t('common.backTo', { page: t('activity.events.title').toLowerCase() }) }}
        </RouterLink>
      </div>

      <div
        v-if="eventStore.loading"
        class="py-8 text-center text-text-muted"
        data-testid="activity-event-detail-loading"
      >
        {{ t('common.actions.loading') }}
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
              {{ t('activity.events.type') }}
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
              {{ t('activity.events.entityType') }}
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
              {{ t('activity.events.entityId') }}
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
              {{ t('activity.events.userId') }}
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
              {{ t('activity.events.occurredAt') }}
            </dt>
            <dd
              class="mt-1 text-text"
              data-testid="activity-event-detail-occurred-at"
            >
              {{ d(new Date(eventStore.selectedEvent.occurredAt), 'long') }}
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-text-muted">
              {{ t('activity.events.payload') }}
            </dt>
            <dd
              class="mt-1"
              data-testid="activity-event-detail-payload"
            >
              <pre class="overflow-auto rounded-lg bg-surface-muted p-4 text-sm text-text">{{
                JSON.stringify(eventStore.selectedEvent.payload, null, 2)
              }}</pre>
            </dd>
          </div>
        </dl>
      </div>
    </div>
  </DashboardLayout>
</template>
