<script setup lang="ts">
import { useI18n } from 'vue-i18n';

import type { Provider } from '@/catalog/types/provider';

import ProviderIcon from '@/catalog/components/ProviderIcon.vue';

defineProps<{
  provider: Provider;
  testingConnection: boolean;
}>();

defineEmits<{
  testConnection: [];
}>();

const { d, t } = useI18n();
</script>

<template>
  <div
    class="mb-6 max-w-3xl rounded-xl border border-border bg-surface"
    data-testid="provider-detail-card"
  >
    <div class="flex items-center justify-between border-b border-border px-6 py-4">
      <div class="flex items-center gap-3">
        <ProviderIcon :type="provider.type" :size="28" />
        <div>
          <h2 class="text-xl font-bold text-text">
            {{ provider.name }}
          </h2>
          <p class="text-xs text-text-muted">
            {{ t(`catalog.providers.types.${provider.type}`) }}
          </p>
        </div>
        <span
          :class="{
            'bg-green-100 text-green-800': provider.status === 'connected',
            'bg-yellow-100 text-yellow-800': provider.status === 'pending',
            'bg-red-100 text-red-800': provider.status === 'error',
          }"
          class="rounded-full px-2 py-0.5 text-xs font-medium"
          data-testid="provider-detail-status"
        >
          {{ t(`catalog.providers.statuses.${provider.status}`) }}
        </span>
      </div>
      <button
        :disabled="testingConnection"
        class="rounded-lg border border-primary bg-transparent px-4 py-2 text-sm font-medium text-primary transition-colors hover:bg-primary hover:text-white disabled:opacity-50"
        data-testid="provider-test-connection"
        @click="$emit('testConnection')"
      >
        {{
          testingConnection ? t('catalog.providers.testing') : t('catalog.providers.testConnection')
        }}
      </button>
    </div>

    <dl class="grid grid-cols-1 gap-4 p-6 sm:grid-cols-2" data-testid="provider-detail-fields">
      <div class="flex items-start gap-3">
        <svg
          class="mt-0.5 h-4 w-4 shrink-0 text-text-muted"
          fill="none"
          stroke="currentColor"
          stroke-width="1.5"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5a17.92 17.92 0 01-8.716-2.247"
          />
        </svg>
        <div>
          <dt class="text-xs font-medium text-text-muted">
            {{ t('catalog.providers.url') }}
          </dt>
          <dd class="mt-0.5">
            <a
              :href="provider.url"
              class="text-sm text-primary hover:underline"
              data-testid="provider-detail-url"
              rel="noopener"
              target="_blank"
            >
              {{ provider.url }}
            </a>
          </dd>
        </div>
      </div>

      <div v-if="provider.username" class="flex items-start gap-3">
        <svg
          class="mt-0.5 h-4 w-4 shrink-0 text-text-muted"
          fill="none"
          stroke="currentColor"
          stroke-width="1.5"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"
          />
        </svg>
        <div>
          <dt class="text-xs font-medium text-text-muted">
            {{ t('catalog.providers.username') }}
          </dt>
          <dd class="mt-0.5 text-sm text-text" data-testid="provider-detail-username">
            {{ provider.username }}
          </dd>
        </div>
      </div>

      <div class="flex items-start gap-3">
        <svg
          class="mt-0.5 h-4 w-4 shrink-0 text-text-muted"
          fill="none"
          stroke="currentColor"
          stroke-width="1.5"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182"
          />
        </svg>
        <div>
          <dt class="text-xs font-medium text-text-muted">
            {{ t('catalog.providers.lastSync') }}
          </dt>
          <dd class="mt-0.5 text-sm text-text" data-testid="provider-detail-last-sync">
            {{
              provider.lastSyncAt ? d(new Date(provider.lastSyncAt), 'short') : t('common.never')
            }}
          </dd>
        </div>
      </div>

      <div class="flex items-start gap-3">
        <svg
          class="mt-0.5 h-4 w-4 shrink-0 text-text-muted"
          fill="none"
          stroke="currentColor"
          stroke-width="1.5"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"
          />
        </svg>
        <div>
          <dt class="text-xs font-medium text-text-muted">
            {{ t('identity.users.createdAt') }}
          </dt>
          <dd class="mt-0.5 text-sm text-text" data-testid="provider-detail-created-at">
            {{ d(new Date(provider.createdAt), 'short') }}
          </dd>
        </div>
      </div>
    </dl>
  </div>
</template>
