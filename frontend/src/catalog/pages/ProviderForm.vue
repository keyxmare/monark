<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink, useRoute, useRouter } from 'vue-router';

import type { ProviderType } from '@/catalog/types/provider';

import ProviderIcon from '@/catalog/components/ProviderIcon.vue';
import { useProviderStore } from '@/catalog/stores/provider';
import DashboardLayout from '@/shared/layouts/DashboardLayout.vue';
import { useToastStore } from '@/shared/stores/toast';

const route = useRoute();
const router = useRouter();
const { d, t } = useI18n();
const providerStore = useProviderStore();
const toastStore = useToastStore();

const providerId = computed(() => route.params.id as string | undefined);
const isEdit = computed(() => !!providerId.value);
const isGitHub = computed(() => form.type === 'github');
const tokenRequired = computed(() => !isEdit.value && !isGitHub.value);

const form = reactive({
  apiToken: '',
  name: '',
  type: 'github' as ProviderType,
  url: '',
  username: '',
});
const touched = reactive({
  apiToken: false,
  name: false,
  url: false,
  username: false,
});
const showToken = ref(false);
const submitting = ref(false);
const formError = ref('');
const loadingProvider = ref(false);
const testingConnection = ref(false);

const errors = computed(() => ({
  apiToken:
    touched.apiToken && tokenRequired.value && !form.apiToken
      ? t('common.validation.required')
      : '',
  name: touched.name && !form.name.trim() ? t('common.validation.required') : '',
  url:
    touched.url && !form.url.trim()
      ? t('common.validation.required')
      : touched.url && !isValidUrl(form.url)
        ? t('common.validation.invalidUrl')
        : '',
  username:
    touched.username && isGitHub.value && !form.apiToken && !form.username.trim()
      ? t('common.validation.required')
      : '',
}));

const hasErrors = computed(() => Object.values(errors.value).some(Boolean));

const statusColor = computed(() => {
  if (!providerStore.selected) return '';
  const map = { connected: 'text-green-600', error: 'text-red-600', pending: 'text-yellow-600' };
  return map[providerStore.selected.status] ?? '';
});

const statusBgColor = computed(() => {
  if (!providerStore.selected) return '';
  const map = {
    connected: 'bg-green-100 text-green-800',
    error: 'bg-red-100 text-red-800',
    pending: 'bg-yellow-100 text-yellow-800',
  };
  return map[providerStore.selected.status] ?? '';
});

function isValidUrl(url: string): boolean {
  try {
    new URL(url);
    return true;
  } catch {
    return false;
  }
}

function touchAll() {
  touched.name = true;
  touched.url = true;
  touched.username = true;
  touched.apiToken = true;
}

onMounted(async () => {
  if (isEdit.value && providerId.value) {
    loadingProvider.value = true;
    await providerStore.fetchOne(providerId.value);
    if (providerStore.selected) {
      form.name = providerStore.selected.name;
      form.type = providerStore.selected.type;
      form.url = providerStore.selected.url;
      form.username = providerStore.selected.username ?? '';
    }
    loadingProvider.value = false;
  }
});

async function handleSubmit() {
  touchAll();
  if (hasErrors.value) return;

  formError.value = '';
  submitting.value = true;

  try {
    if (isEdit.value && providerId.value) {
      await providerStore.update(providerId.value, {
        apiToken: form.apiToken || undefined,
        name: form.name,
        url: form.url,
        username: form.username || undefined,
      });
      router.push({ name: 'catalog-providers-detail', params: { id: providerId.value } });
    } else {
      const provider = await providerStore.create({
        apiToken: form.apiToken || undefined,
        name: form.name,
        type: form.type,
        url: form.url,
        username: form.username || undefined,
      });
      router.push({ name: 'catalog-providers-detail', params: { id: provider.id } });
    }
  } catch {
    formError.value = isEdit.value
      ? t('catalog.providers.updateFailed')
      : t('catalog.providers.createFailed');
  } finally {
    submitting.value = false;
  }
}

async function handleTestConnection() {
  if (!providerId.value) return;
  testingConnection.value = true;
  const connected = await providerStore.testConnection(providerId.value);
  testingConnection.value = false;
  toastStore.addToast({
    title: connected
      ? t('catalog.providers.connectionSuccess', { name: providerStore.selected?.name ?? '' })
      : t('catalog.providers.connectionFailed', { name: providerStore.selected?.name ?? '' }),
    variant: connected ? 'success' : 'error',
  });
}
</script>

<template>
  <DashboardLayout>
    <div data-testid="provider-form-page">
      <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-2 text-sm text-text-muted">
          <RouterLink
            :to="{ name: 'catalog-providers-list' }"
            class="text-primary hover:text-primary-dark"
            data-testid="provider-form-back"
          >
            {{ t('catalog.providers.title') }}
          </RouterLink>
          <span>/</span>
          <span
            v-if="isEdit && providerStore.selected"
            class="flex items-center gap-1.5"
          >
            <RouterLink
              :to="{ name: 'catalog-providers-detail', params: { id: providerId } }"
              class="text-primary hover:text-primary-dark"
            >
              {{ providerStore.selected.name }}
            </RouterLink>
            <span>/</span>
            <span class="text-text">{{ t('common.actions.edit') }}</span>
          </span>
          <span
            v-else
            class="text-text"
          >{{ t('catalog.providers.createProvider') }}</span>
        </div>
      </div>

      <div
        v-if="loadingProvider"
        class="py-8 text-center text-text-muted"
        data-testid="provider-form-loading"
      >
        {{ t('common.actions.loading') }}
      </div>

      <div
        v-else
        class="grid grid-cols-1 gap-6 lg:grid-cols-3"
      >
        <div class="lg:col-span-2">
          <div class="rounded-xl border border-border bg-surface p-6">
            <div class="mb-6 flex items-center gap-3">
              <ProviderIcon
                v-if="isEdit || form.type"
                :type="form.type"
                :size="28"
              />
              <h2 class="text-xl font-bold text-text">
                {{
                  isEdit
                    ? t('catalog.providers.editProvider')
                    : t('catalog.providers.createProvider')
                }}
              </h2>
            </div>

            <form
              data-testid="provider-form"
              @submit.prevent="handleSubmit"
            >
              <div
                v-if="formError"
                class="mb-4 rounded-lg bg-danger/10 p-3 text-sm text-danger"
                role="alert"
                data-testid="provider-form-error"
              >
                {{ formError }}
              </div>

              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                  <label
                    for="field-name"
                    class="mb-1 block text-sm font-medium text-text"
                  >{{ t('catalog.providers.name') }} <span class="text-danger">*</span></label>
                  <input
                    id="field-name"
                    v-model="form.name"
                    type="text"
                    required
                    :class="
                      errors.name
                        ? 'border-danger focus:border-danger focus:ring-danger/20'
                        : 'border-border focus:border-primary focus:ring-primary/20'
                    "
                    class="w-full rounded-lg border px-3 py-2 text-text focus:ring-2 focus:outline-none"
                    data-testid="field-name"
                    @blur="touched.name = true"
                  >
                  <p
                    v-if="errors.name"
                    class="mt-1 text-xs text-danger"
                    data-testid="error-name"
                  >
                    {{ errors.name }}
                  </p>
                </div>

                <div>
                  <label
                    for="field-type"
                    class="mb-1 block text-sm font-medium text-text"
                  >{{ t('catalog.providers.type') }}
                    <span
                      v-if="!isEdit"
                      class="text-danger"
                    >*</span></label>
                  <select
                    id="field-type"
                    v-model="form.type"
                    required
                    :disabled="isEdit"
                    class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                    data-testid="field-type"
                  >
                    <option value="gitlab">
                      GitLab
                    </option>
                    <option value="github">
                      GitHub
                    </option>
                    <option value="bitbucket">
                      Bitbucket
                    </option>
                  </select>
                  <p
                    v-if="isEdit"
                    class="mt-1 text-xs text-text-muted"
                  >
                    {{ t('catalog.providers.typeLockedHint') }}
                  </p>
                </div>
              </div>

              <div class="mt-4">
                <label
                  for="field-url"
                  class="mb-1 block text-sm font-medium text-text"
                >{{ t('catalog.providers.url') }} <span class="text-danger">*</span></label>
                <input
                  id="field-url"
                  v-model="form.url"
                  type="url"
                  required
                  :placeholder="isGitHub ? 'https://api.github.com' : 'https://gitlab.example.com'"
                  :class="
                    errors.url
                      ? 'border-danger focus:border-danger focus:ring-danger/20'
                      : 'border-border focus:border-primary focus:ring-primary/20'
                  "
                  class="w-full rounded-lg border px-3 py-2 text-text focus:ring-2 focus:outline-none"
                  data-testid="field-url"
                  @blur="touched.url = true"
                >
                <p
                  v-if="errors.url"
                  class="mt-1 text-xs text-danger"
                  data-testid="error-url"
                >
                  {{ errors.url }}
                </p>
                <p
                  v-else
                  class="mt-1 text-xs text-text-muted"
                >
                  {{ t('catalog.providers.urlHint') }}
                </p>
              </div>

              <div
                v-if="isGitHub"
                class="mt-4"
              >
                <label
                  for="field-username"
                  class="mb-1 block text-sm font-medium text-text"
                >{{
                  t('catalog.providers.username')
                }}</label>
                <input
                  id="field-username"
                  v-model="form.username"
                  type="text"
                  :required="isGitHub && !form.apiToken"
                  placeholder="keyxmare"
                  :class="
                    errors.username
                      ? 'border-danger focus:border-danger focus:ring-danger/20'
                      : 'border-border focus:border-primary focus:ring-primary/20'
                  "
                  class="w-full rounded-lg border px-3 py-2 text-text focus:ring-2 focus:outline-none"
                  data-testid="field-username"
                  @blur="touched.username = true"
                >
                <p
                  v-if="errors.username"
                  class="mt-1 text-xs text-danger"
                  data-testid="error-username"
                >
                  {{ errors.username }}
                </p>
                <p
                  v-else
                  class="mt-1 text-xs text-text-muted"
                >
                  {{ t('catalog.providers.usernameHint') }}
                </p>
              </div>

              <div class="mt-4">
                <label
                  for="field-apiToken"
                  class="mb-1 block text-sm font-medium text-text"
                >{{ t('catalog.providers.apiToken') }}
                  <span
                    v-if="tokenRequired"
                    class="text-danger"
                  >*</span></label>
                <div class="relative">
                  <input
                    id="field-apiToken"
                    v-model="form.apiToken"
                    :type="showToken ? 'text' : 'password'"
                    :required="tokenRequired"
                    :placeholder="
                      isEdit
                        ? t('catalog.providers.tokenKeepCurrent')
                        : t('catalog.providers.tokenPlaceholder')
                    "
                    :class="
                      errors.apiToken
                        ? 'border-danger focus:border-danger focus:ring-danger/20'
                        : 'border-border focus:border-primary focus:ring-primary/20'
                    "
                    class="w-full rounded-lg border px-3 py-2 pr-16 text-text focus:ring-2 focus:outline-none"
                    data-testid="field-apiToken"
                    @blur="touched.apiToken = true"
                  >
                  <button
                    type="button"
                    class="absolute top-1/2 right-3 -translate-y-1/2 text-xs text-text-muted hover:text-text"
                    data-testid="toggle-token-visibility"
                    @click="showToken = !showToken"
                  >
                    {{
                      showToken
                        ? t('catalog.providers.hideToken')
                        : t('catalog.providers.showToken')
                    }}
                  </button>
                </div>
                <p
                  v-if="errors.apiToken"
                  class="mt-1 text-xs text-danger"
                  data-testid="error-apiToken"
                >
                  {{ errors.apiToken }}
                </p>
                <p
                  v-else-if="isGitHub"
                  class="mt-1 text-xs text-text-muted"
                >
                  {{ t('catalog.providers.tokenOptionalHint') }}
                </p>
              </div>

              <div class="mt-6 flex items-center gap-3">
                <button
                  type="submit"
                  :disabled="submitting"
                  class="rounded-lg bg-primary px-6 py-2.5 font-medium text-white transition-colors hover:bg-primary-dark disabled:opacity-50"
                  data-testid="provider-form-submit"
                >
                  <span
                    v-if="submitting"
                    class="flex items-center gap-2"
                  >
                    <svg
                      class="h-4 w-4 animate-spin"
                      fill="none"
                      viewBox="0 0 24 24"
                    >
                      <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                      />
                      <path
                        class="opacity-75"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                        fill="currentColor"
                      />
                    </svg>
                    {{ t('common.saving') }}
                  </span>
                  <span v-else>
                    {{
                      isEdit
                        ? t('catalog.providers.updateProvider')
                        : t('catalog.providers.createProvider')
                    }}
                  </span>
                </button>
                <RouterLink
                  :to="
                    isEdit && providerId
                      ? { name: 'catalog-providers-detail', params: { id: providerId } }
                      : { name: 'catalog-providers-list' }
                  "
                  class="rounded-lg border border-border px-6 py-2.5 font-medium text-text transition-colors hover:bg-surface-muted"
                  data-testid="provider-form-cancel"
                >
                  {{ t('common.actions.cancel') }}
                </RouterLink>
              </div>
            </form>
          </div>
        </div>

        <div
          v-if="isEdit && providerStore.selected"
          class="space-y-4"
        >
          <div
            class="rounded-xl border border-border bg-surface p-5"
            data-testid="provider-sidebar-info"
          >
            <h3 class="mb-4 text-sm font-semibold text-text">
              {{ t('catalog.providers.health.status') }}
            </h3>

            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-sm text-text-muted">{{ t('catalog.providers.status') }}</span>
                <span
                  :class="statusBgColor"
                  class="rounded-full px-2 py-0.5 text-xs font-medium"
                  data-testid="sidebar-status"
                >
                  {{ t(`catalog.providers.statuses.${providerStore.selected.status}`) }}
                </span>
              </div>

              <div class="flex items-center justify-between">
                <span class="text-sm text-text-muted">{{ t('catalog.providers.projects') }}</span>
                <span
                  class="text-sm font-semibold tabular-nums text-text"
                  data-testid="sidebar-projects"
                >
                  {{ providerStore.selected.projectsCount }}
                </span>
              </div>

              <div class="flex items-center justify-between">
                <span class="text-sm text-text-muted">{{ t('catalog.providers.lastSync') }}</span>
                <span
                  class="text-sm text-text"
                  data-testid="sidebar-last-sync"
                >
                  {{
                    providerStore.selected.lastSyncAt
                      ? d(new Date(providerStore.selected.lastSyncAt), 'short')
                      : t('common.never')
                  }}
                </span>
              </div>

              <div class="flex items-center justify-between">
                <span class="text-sm text-text-muted">{{ t('identity.users.createdAt') }}</span>
                <span class="text-sm text-text">
                  {{ d(new Date(providerStore.selected.createdAt), 'short') }}
                </span>
              </div>
            </div>
          </div>

          <button
            :disabled="testingConnection"
            class="flex w-full items-center justify-center gap-2 rounded-lg border border-primary bg-transparent px-4 py-2.5 text-sm font-medium text-primary transition-colors hover:bg-primary hover:text-white disabled:opacity-50"
            data-testid="provider-form-test-connection"
            @click="handleTestConnection"
          >
            <svg
              v-if="testingConnection"
              class="h-4 w-4 animate-spin"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
              />
              <path
                class="opacity-75"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                fill="currentColor"
              />
            </svg>
            {{
              testingConnection
                ? t('catalog.providers.testing')
                : t('catalog.providers.testConnection')
            }}
          </button>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
