<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, useRouter } from 'vue-router'

import type { TokenProvider } from '@/identity/types/access-token'

import { useAccessTokenStore } from '@/identity/stores/access-token'
import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'

const router = useRouter()
const { t } = useI18n()
const tokenStore = useAccessTokenStore()

const provider = ref<TokenProvider>('gitlab')
const token = ref('')
const scopes = ref('')
const expiresAt = ref('')
const submitting = ref(false)
const error = ref('')

async function handleSubmit() {
  error.value = ''
  submitting.value = true

  try {
    await tokenStore.create({
      expiresAt: expiresAt.value || undefined,
      provider: provider.value,
      scopes: scopes.value ? scopes.value.split(',').map(s => s.trim()) : [],
      token: token.value,
    })
    router.push({ name: 'identity-access-tokens-list' })
  } catch {
    error.value = t('identity.accessTokens.createFailed')
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <DashboardLayout>
    <div data-testid="access-token-form-page">
      <div class="mb-6">
        <RouterLink
          :to="{ name: 'identity-access-tokens-list' }"
          class="text-sm text-primary hover:text-primary-dark"
          data-testid="access-token-form-back"
        >
          &larr; {{ t('common.backTo', { page: t('identity.accessTokens.title').toLowerCase() }) }}
        </RouterLink>
      </div>

      <div class="max-w-lg rounded-xl border border-border bg-surface p-6">
        <h2 class="mb-6 text-2xl font-bold text-text">
          {{ t('identity.accessTokens.addToken') }}
        </h2>

        <form
          data-testid="access-token-form"
          @submit.prevent="handleSubmit"
        >
          <div
            v-if="error"
            class="mb-4 rounded-lg bg-danger/10 p-3 text-sm text-danger"
            role="alert"
            data-testid="access-token-form-error"
          >
            {{ error }}
          </div>

          <div class="mb-4">
            <label
              for="provider"
              class="mb-1 block text-sm font-medium text-text"
            >{{ t('identity.accessTokens.provider') }}</label>
            <select
              id="provider"
              v-model="provider"
              class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
              data-testid="access-token-provider"
            >
              <option value="gitlab">
                GitLab
              </option>
              <option value="github">
                GitHub
              </option>
            </select>
          </div>

          <div class="mb-4">
            <label
              for="token"
              class="mb-1 block text-sm font-medium text-text"
            >{{ t('identity.accessTokens.token') }}</label>
            <input
              id="token"
              v-model="token"
              type="text"
              required
              class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
              data-testid="access-token-token"
            >
          </div>

          <div class="mb-4">
            <label
              for="scopes"
              class="mb-1 block text-sm font-medium text-text"
            >{{ t('identity.accessTokens.scopesHint') }}</label>
            <input
              id="scopes"
              v-model="scopes"
              type="text"
              :placeholder="t('identity.accessTokens.scopesPlaceholder')"
              class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
              data-testid="access-token-scopes"
            >
          </div>

          <div class="mb-6">
            <label
              for="expiresAt"
              class="mb-1 block text-sm font-medium text-text"
            >{{ t('identity.accessTokens.expiresAtOptional') }}</label>
            <input
              id="expiresAt"
              v-model="expiresAt"
              type="datetime-local"
              class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
              data-testid="access-token-expires-at"
            >
          </div>

          <button
            type="submit"
            :disabled="submitting"
            class="w-full rounded-lg bg-primary px-4 py-2.5 font-medium text-white transition-colors hover:bg-primary-dark disabled:opacity-50"
            data-testid="access-token-submit"
          >
            {{ submitting ? t('common.creating') : t('identity.accessTokens.createToken') }}
          </button>
        </form>
      </div>
    </div>
  </DashboardLayout>
</template>
