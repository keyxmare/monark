<script setup lang="ts">
import { computed, onMounted, watch } from 'vue';
import { useI18n } from 'vue-i18n';

import type { MessengerStats } from '@/activity/types/messenger';

import { useMessengerStore } from '@/activity/stores/messenger';
import { useMercure } from '@/shared/composables/useMercure';
import DashboardLayout from '@/shared/layouts/DashboardLayout.vue';

const { t } = useI18n();
const messengerStore = useMessengerStore();

const { connected, data: liveStats } = useMercure<MessengerStats>('/messenger/stats');

watch(liveStats, (stats) => {
  if (stats) {
    messengerStore.queues = stats.queues;
    messengerStore.workers = stats.workers;
  }
});

const totalMessages = computed(() => messengerStore.queues.reduce((sum, q) => sum + q.messages, 0));

function queueHealthClass(queue: { messages: number }): string {
  const hasWorkers = messengerStore.workers.length > 0;
  if (!hasWorkers) return 'bg-red-100 text-red-800';
  if (queue.messages > 100) return 'bg-orange-100 text-orange-800';
  if (queue.messages > 10) return 'bg-yellow-100 text-yellow-800';
  return 'bg-green-100 text-green-800';
}

function queueHealthLabel(queue: { messages: number }): string {
  const hasWorkers = messengerStore.workers.length > 0;
  if (!hasWorkers) return t('activity.messenger.noWorker');
  if (queue.messages > 100) return t('activity.messenger.highBacklog');
  if (queue.messages > 10) return t('activity.messenger.moderate');
  return t('activity.messenger.healthy');
}

function workerStateClass(state: string): string {
  return state === 'running' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800';
}

onMounted(() => {
  messengerStore.fetchStats();
});
</script>

<template>
  <DashboardLayout>
    <div data-testid="messenger-monitor-page">
      <div class="mb-6 flex items-center justify-between">
        <h2 class="text-2xl font-bold text-text" data-testid="messenger-title">
          {{ t('activity.messenger.title') }}
        </h2>
        <div class="flex items-center gap-3">
          <span class="flex items-center gap-2 text-sm" data-testid="sse-status">
            <span
              class="inline-block h-2 w-2 rounded-full"
              :class="connected ? 'bg-green-500' : 'bg-red-500'"
            />
            {{
              connected ? t('activity.messenger.liveUpdates') : t('activity.messenger.reconnecting')
            }}
          </span>
          <button
            class="rounded-lg border border-border bg-surface px-3 py-2 text-sm font-medium text-text hover:bg-background"
            data-testid="refresh-btn"
            @click="messengerStore.fetchStats()"
          >
            {{ t('common.actions.refresh') }}
          </button>
        </div>
      </div>

      <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-3" data-testid="messenger-summary">
        <div class="rounded-xl border border-border bg-surface p-4 shadow-sm">
          <p class="text-sm font-medium text-text-muted">
            {{ t('activity.messenger.queuesCount') }}
          </p>
          <p class="mt-1 text-2xl font-bold text-text">
            {{ messengerStore.queues.length }}
          </p>
        </div>
        <div class="rounded-xl border border-border bg-surface p-4 shadow-sm">
          <p class="text-sm font-medium text-text-muted">
            {{ t('activity.messenger.totalMessages') }}
          </p>
          <p class="mt-1 text-2xl font-bold text-text">
            {{ totalMessages }}
          </p>
        </div>
        <div class="rounded-xl border border-border bg-surface p-4 shadow-sm">
          <p class="text-sm font-medium text-text-muted">
            {{ t('activity.messenger.workers') }}
          </p>
          <p
            class="mt-1 text-2xl font-bold"
            :class="messengerStore.workers.length > 0 ? 'text-green-600' : 'text-red-600'"
          >
            {{ messengerStore.workers.length }}
          </p>
        </div>
      </div>

      <div
        v-if="messengerStore.loading && messengerStore.queues.length === 0"
        class="py-8 text-center text-text-muted"
        data-testid="messenger-loading"
      >
        {{ t('common.actions.loading') }}
      </div>

      <div
        v-else-if="messengerStore.error"
        class="rounded-lg bg-danger/10 p-4 text-danger"
        role="alert"
        data-testid="messenger-error"
      >
        {{ messengerStore.error }}
      </div>

      <template v-else>
        <div v-if="messengerStore.workers.length > 0" class="mb-6" data-testid="workers-section">
          <h3 class="mb-3 text-lg font-semibold text-text">
            {{ t('activity.messenger.workersTitle') }}
          </h3>
          <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <div
              v-for="(worker, idx) in messengerStore.workers"
              :key="idx"
              class="flex items-center justify-between rounded-lg border border-border bg-surface px-4 py-3"
              :data-testid="`worker-card-${idx}`"
            >
              <div>
                <p class="text-sm font-medium text-text">
                  {{ worker.connection }}
                </p>
                <p class="text-xs text-text-muted">prefetch: {{ worker.prefetch }}</p>
              </div>
              <span
                :class="workerStateClass(worker.state)"
                class="rounded-full px-2 py-0.5 text-xs font-medium"
              >
                {{ worker.state }}
              </span>
            </div>
          </div>
        </div>

        <div
          v-else
          class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
          data-testid="no-workers-warning"
        >
          {{ t('activity.messenger.noWorkerWarning') }}
        </div>

        <h3 class="mb-3 text-lg font-semibold text-text">
          {{ t('activity.messenger.queuesTitle') }}
        </h3>
        <div
          class="overflow-x-auto rounded-xl border border-border bg-surface"
          data-testid="messenger-table"
        >
          <table class="w-full text-left text-sm">
            <thead class="border-b border-border bg-background">
              <tr>
                <th class="px-4 py-3 font-medium text-text-muted">
                  {{ t('activity.messenger.queueName') }}
                </th>
                <th class="px-4 py-3 font-medium text-text-muted">
                  {{ t('activity.messenger.health') }}
                </th>
                <th class="px-4 py-3 text-right font-medium text-text-muted">
                  {{ t('activity.messenger.messages') }}
                </th>
                <th class="px-4 py-3 text-right font-medium text-text-muted">
                  {{ t('activity.messenger.ready') }}
                </th>
                <th class="px-4 py-3 text-right font-medium text-text-muted">
                  {{ t('activity.messenger.unacked') }}
                </th>
                <th class="px-4 py-3 text-right font-medium text-text-muted">
                  {{ t('activity.messenger.publishRate') }}
                </th>
                <th class="px-4 py-3 text-right font-medium text-text-muted">
                  {{ t('activity.messenger.deliverRate') }}
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="queue in messengerStore.queues"
                :key="queue.name"
                class="border-b border-border last:border-0"
                :data-testid="`queue-row-${queue.name}`"
              >
                <td class="px-4 py-3 font-medium text-text">
                  {{ queue.name }}
                </td>
                <td class="px-4 py-3">
                  <span
                    :class="queueHealthClass(queue)"
                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                  >
                    {{ queueHealthLabel(queue) }}
                  </span>
                </td>
                <td class="px-4 py-3 text-right tabular-nums text-text">
                  {{ queue.messages }}
                </td>
                <td class="px-4 py-3 text-right tabular-nums text-text">
                  {{ queue.messages_ready }}
                </td>
                <td class="px-4 py-3 text-right tabular-nums text-text">
                  {{ queue.messages_unacknowledged }}
                </td>
                <td class="px-4 py-3 text-right tabular-nums text-text-muted">
                  {{ queue.publish_rate.toFixed(1) }}/s
                </td>
                <td class="px-4 py-3 text-right tabular-nums text-text-muted">
                  {{ queue.deliver_rate.toFixed(1) }}/s
                </td>
              </tr>
            </tbody>
          </table>

          <div
            v-if="messengerStore.queues.length === 0"
            class="py-8 text-center text-text-muted"
            data-testid="messenger-empty"
          >
            {{ t('activity.messenger.noQueues') }}
          </div>
        </div>
      </template>
    </div>
  </DashboardLayout>
</template>
