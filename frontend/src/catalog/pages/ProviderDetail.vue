<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'

import type { RemoteProject } from '@/catalog/types/provider'

import { useProviderStore } from '@/catalog/stores/provider'
import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'

const route = useRoute()
const router = useRouter()
const providerStore = useProviderStore()

const providerId = computed(() => route.params.id as string)
const selectedIds = ref<string[]>([])
const testingConnection = ref(false)
const importing = ref(false)

onMounted(async () => {
  await providerStore.fetchOne(providerId.value)
  await providerStore.fetchRemoteProjects(providerId.value)
})

function getSelectedRemoteProjects(): RemoteProject[] {
  return providerStore.remoteProjects.filter(rp => selectedIds.value.includes(rp.externalId))
}

async function handleDelete() {
  await providerStore.remove(providerId.value)
  router.push({ name: 'catalog-providers-list' })
}

async function handleImport() {
  const selected = getSelectedRemoteProjects()
  if (selected.length === 0) return

  importing.value = true
  try {
    await providerStore.importProjects(providerId.value, {
      projects: selected.map(rp => ({
        defaultBranch: rp.defaultBranch,
        description: rp.description,
        externalId: rp.externalId,
        name: rp.name,
        repositoryUrl: rp.repositoryUrl,
        slug: rp.slug,
        visibility: rp.visibility,
      })),
    })
    selectedIds.value = []
  } finally {
    importing.value = false
  }
}

async function handleTestConnection() {
  testingConnection.value = true
  await providerStore.testConnection(providerId.value)
  testingConnection.value = false
}
</script>

<template>
  <DashboardLayout>
    <div data-testid="provider-detail-page">
      <div class="mb-6 flex items-center justify-between">
        <RouterLink
          :to="{ name: 'catalog-providers-list' }"
          class="text-sm text-primary hover:text-primary-dark"
          data-testid="provider-detail-back"
        >
          &larr; Back to providers
        </RouterLink>
        <div
          v-if="providerStore.selected"
          class="flex items-center gap-3"
        >
          <RouterLink
            :to="{ name: 'catalog-providers-edit', params: { id: providerStore.selected.id } }"
            class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark"
            data-testid="provider-detail-edit"
          >
            Edit
          </RouterLink>
          <button
            class="rounded-lg bg-danger px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-danger/80"
            data-testid="provider-detail-delete"
            @click="handleDelete"
          >
            Delete
          </button>
        </div>
      </div>

      <div
        v-if="providerStore.loading && !providerStore.selected"
        class="py-8 text-center text-text-muted"
        data-testid="provider-detail-loading"
      >
        Loading...
      </div>

      <div
        v-else-if="providerStore.error"
        class="rounded-lg bg-danger/10 p-4 text-danger"
        role="alert"
        data-testid="provider-detail-error"
      >
        {{ providerStore.error }}
      </div>

      <template v-else-if="providerStore.selected">
        <div
          class="mb-6 max-w-2xl rounded-xl border border-border bg-surface p-6"
          data-testid="provider-detail-card"
        >
          <div class="mb-6 flex items-center gap-4">
            <h2 class="text-2xl font-bold text-text">
              {{ providerStore.selected.name }}
            </h2>
            <span
              :class="{
                'bg-green-100 text-green-800': providerStore.selected.status === 'connected',
                'bg-yellow-100 text-yellow-800': providerStore.selected.status === 'pending',
                'bg-red-100 text-red-800': providerStore.selected.status === 'error',
              }"
              class="rounded-full px-2 py-0.5 text-xs font-medium"
              data-testid="provider-detail-status"
            >
              {{ providerStore.selected.status }}
            </span>
          </div>

          <dl class="space-y-4">
            <div>
              <dt class="text-sm font-medium text-text-muted">
                Type
              </dt>
              <dd class="mt-1">
                <span
                  :class="{
                    'bg-orange-100 text-orange-800': providerStore.selected.type === 'gitlab',
                    'bg-gray-100 text-gray-800': providerStore.selected.type === 'github',
                    'bg-blue-100 text-blue-800': providerStore.selected.type === 'bitbucket',
                  }"
                  class="rounded-full px-2 py-0.5 text-xs font-medium"
                  data-testid="provider-detail-type"
                >
                  {{ providerStore.selected.type }}
                </span>
              </dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-text-muted">
                URL
              </dt>
              <dd
                class="mt-1 text-text"
                data-testid="provider-detail-url"
              >
                {{ providerStore.selected.url }}
              </dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-text-muted">
                Last Sync
              </dt>
              <dd
                class="mt-1 text-text"
                data-testid="provider-detail-last-sync"
              >
                {{ providerStore.selected.lastSyncAt ? new Date(providerStore.selected.lastSyncAt).toLocaleDateString() : 'Never' }}
              </dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-text-muted">
                Created At
              </dt>
              <dd
                class="mt-1 text-text"
                data-testid="provider-detail-created-at"
              >
                {{ new Date(providerStore.selected.createdAt).toLocaleDateString() }}
              </dd>
            </div>
          </dl>

          <div class="mt-6">
            <button
              :disabled="testingConnection"
              class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark disabled:opacity-50"
              data-testid="provider-test-connection"
              @click="handleTestConnection"
            >
              {{ testingConnection ? 'Testing...' : 'Test Connection' }}
            </button>
          </div>
        </div>

        <div class="mt-8">
          <div class="mb-4 flex items-center justify-between">
            <h3 class="text-xl font-bold text-text">
              Remote Projects
            </h3>
            <button
              v-if="selectedIds.length > 0"
              :disabled="importing"
              class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark disabled:opacity-50"
              data-testid="provider-import-selected"
              @click="handleImport"
            >
              {{ importing ? 'Importing...' : `Import Selected (${selectedIds.length})` }}
            </button>
          </div>

          <div
            v-if="providerStore.loading"
            class="py-8 text-center text-text-muted"
            data-testid="remote-projects-loading"
          >
            Loading remote projects...
          </div>

          <div
            v-else
            class="rounded-xl border border-border bg-surface"
            data-testid="remote-projects-list"
          >
            <div
              v-for="project in providerStore.remoteProjects"
              :key="project.externalId"
              class="flex items-center gap-4 border-b border-border px-4 py-3 last:border-0"
            >
              <input
                v-if="!project.alreadyImported"
                v-model="selectedIds"
                type="checkbox"
                :value="project.externalId"
                :aria-label="`Select ${project.name}`"
                :data-testid="`select-${project.externalId}`"
              >
              <span
                v-else
                class="rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-800"
                data-testid="remote-project-imported-badge"
              >
                Imported
              </span>
              <div class="flex-1">
                <p class="font-medium text-text">
                  {{ project.name }}
                </p>
                <p class="text-sm text-text-muted">
                  {{ project.slug }}
                </p>
              </div>
              <span class="text-sm text-text-muted">
                {{ project.visibility }}
              </span>
              <span class="text-sm text-text-muted">
                {{ project.defaultBranch }}
              </span>
            </div>

            <div
              v-if="providerStore.remoteProjects.length === 0"
              class="py-8 text-center text-text-muted"
              data-testid="remote-projects-empty"
            >
              No remote projects found.
            </div>
          </div>
        </div>
      </template>
    </div>
  </DashboardLayout>
</template>
