<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, useRoute, useRouter } from 'vue-router'

import type { ProviderType } from '@/catalog/types/provider'

import { useProviderStore } from '@/catalog/stores/provider'
import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const providerStore = useProviderStore()

const providerId = computed(() => route.params.id as string | undefined)
const isEdit = computed(() => !!providerId.value)

const form = ref({
  apiToken: '',
  name: '',
  type: 'gitlab' as ProviderType,
  url: '',
})
const showToken = ref(false)
const submitting = ref(false)
const formError = ref('')

onMounted(async () => {
  if (isEdit.value && providerId.value) {
    await providerStore.fetchOne(providerId.value)
    if (providerStore.selected) {
      form.value.name = providerStore.selected.name
      form.value.type = providerStore.selected.type
      form.value.url = providerStore.selected.url
    }
  }
})

async function handleSubmit() {
  formError.value = ''
  submitting.value = true

  try {
    if (isEdit.value && providerId.value) {
      await providerStore.update(providerId.value, {
        apiToken: form.value.apiToken || undefined,
        name: form.value.name,
        url: form.value.url,
      })
      router.push({ name: 'catalog-providers-detail', params: { id: providerId.value } })
    } else {
      const provider = await providerStore.create({
        apiToken: form.value.apiToken,
        name: form.value.name,
        type: form.value.type,
        url: form.value.url,
      })
      router.push({ name: 'catalog-providers-detail', params: { id: provider.id } })
    }
  } catch {
    formError.value = isEdit.value ? t('catalog.providers.updateFailed') : t('catalog.providers.createFailed')
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <DashboardLayout>
    <div data-testid="provider-form-page">
      <div class="mb-6">
        <RouterLink
          :to="{ name: 'catalog-providers-list' }"
          class="text-sm text-primary hover:text-primary-dark"
          data-testid="provider-form-back"
        >
          &larr; {{ t('common.backTo', { page: t('catalog.providers.title').toLowerCase() }) }}
        </RouterLink>
      </div>

      <div class="max-w-lg rounded-xl border border-border bg-surface p-6">
        <h2 class="mb-6 text-2xl font-bold text-text">
          {{ isEdit ? t('catalog.providers.editProvider') : t('catalog.providers.createProvider') }}
        </h2>

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

          <div class="mb-4">
            <label
              for="field-name"
              class="mb-1 block text-sm font-medium text-text"
            >{{ t('catalog.providers.name') }}</label>
            <input
              id="field-name"
              v-model="form.name"
              type="text"
              required
              class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
              data-testid="field-name"
            >
          </div>

          <div class="mb-4">
            <label
              for="field-type"
              class="mb-1 block text-sm font-medium text-text"
            >{{ t('catalog.providers.type') }}</label>
            <select
              id="field-type"
              v-model="form.type"
              required
              :disabled="isEdit"
              class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none disabled:opacity-50"
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
          </div>

          <div class="mb-4">
            <label
              for="field-url"
              class="mb-1 block text-sm font-medium text-text"
            >{{ t('catalog.providers.url') }}</label>
            <input
              id="field-url"
              v-model="form.url"
              type="url"
              required
              placeholder="https://gitlab.example.com"
              class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
              data-testid="field-url"
            >
          </div>

          <div class="mb-6">
            <label
              for="field-apiToken"
              class="mb-1 block text-sm font-medium text-text"
            >{{ t('catalog.providers.apiToken') }}</label>
            <div class="relative">
              <input
                id="field-apiToken"
                v-model="form.apiToken"
                :type="showToken ? 'text' : 'password'"
                :required="!isEdit"
                :placeholder="isEdit ? t('catalog.providers.tokenKeepCurrent') : t('catalog.providers.tokenPlaceholder')"
                class="w-full rounded-lg border border-border px-3 py-2 pr-16 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
                data-testid="field-apiToken"
              >
              <button
                type="button"
                class="absolute top-1/2 right-3 -translate-y-1/2 text-xs text-text-muted hover:text-text"
                data-testid="toggle-token-visibility"
                @click="showToken = !showToken"
              >
                {{ showToken ? t('catalog.providers.hideToken') : t('catalog.providers.showToken') }}
              </button>
            </div>
          </div>

          <button
            type="submit"
            :disabled="submitting"
            class="w-full rounded-lg bg-primary px-4 py-2.5 font-medium text-white transition-colors hover:bg-primary-dark disabled:opacity-50"
            data-testid="provider-form-submit"
          >
            {{ submitting ? t('common.saving') : (isEdit ? t('catalog.providers.updateProvider') : t('catalog.providers.createProvider')) }}
          </button>
        </form>
      </div>
    </div>
  </DashboardLayout>
</template>
