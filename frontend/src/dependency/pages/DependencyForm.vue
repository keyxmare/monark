<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, useRoute, useRouter } from 'vue-router'

import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'
import { useDependencyStore } from '@/dependency/stores/dependency'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const dependencyStore = useDependencyStore()

const dependencyId = computed(() => route.params.id as string | undefined)
const isEditMode = computed(() => !!dependencyId.value)

const name = ref('')
const currentVersion = ref('')
const latestVersion = ref('')
const ltsVersion = ref('')
const packageManager = ref('composer')
const type = ref('runtime')
const isOutdated = ref(false)
const repositoryUrl = ref('')
const projectId = ref('')
const submitting = ref(false)
const formError = ref('')

onMounted(async () => {
  if (isEditMode.value && dependencyId.value) {
    await dependencyStore.fetchOne(dependencyId.value)
    if (dependencyStore.selectedDependency) {
      name.value = dependencyStore.selectedDependency.name
      currentVersion.value = dependencyStore.selectedDependency.currentVersion
      latestVersion.value = dependencyStore.selectedDependency.latestVersion
      ltsVersion.value = dependencyStore.selectedDependency.ltsVersion
      packageManager.value = dependencyStore.selectedDependency.packageManager
      type.value = dependencyStore.selectedDependency.type
      isOutdated.value = dependencyStore.selectedDependency.isOutdated
      repositoryUrl.value = dependencyStore.selectedDependency.repositoryUrl ?? ''
      projectId.value = dependencyStore.selectedDependency.projectId
    }
  }
})

async function handleSubmit() {
  formError.value = ''
  submitting.value = true

  try {
    if (isEditMode.value && dependencyId.value) {
      await dependencyStore.update(dependencyId.value, {
        name: name.value,
        currentVersion: currentVersion.value,
        latestVersion: latestVersion.value,
        ltsVersion: ltsVersion.value,
        packageManager: packageManager.value as 'composer' | 'npm' | 'pip',
        type: type.value as 'runtime' | 'dev',
        isOutdated: isOutdated.value,
        repositoryUrl: repositoryUrl.value || null,
      })
      router.push({ name: 'dependency-dependencies-detail', params: { id: dependencyId.value } })
    } else {
      const dep = await dependencyStore.create({
        name: name.value,
        currentVersion: currentVersion.value,
        latestVersion: latestVersion.value,
        ltsVersion: ltsVersion.value,
        packageManager: packageManager.value as 'composer' | 'npm' | 'pip',
        type: type.value as 'runtime' | 'dev',
        isOutdated: isOutdated.value,
        projectId: projectId.value,
        repositoryUrl: repositoryUrl.value || null,
      })
      router.push({ name: 'dependency-dependencies-detail', params: { id: dep.id } })
    }
  } catch {
    formError.value = isEditMode.value
      ? t('dependency.dependencies.updateFailed')
      : t('dependency.dependencies.createFailed')
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <DashboardLayout>
    <div data-testid="dependency-form-page">
      <div class="mb-6">
        <RouterLink
          :to="{ name: 'dependency-dependencies-list' }"
          class="text-sm text-primary hover:text-primary-dark"
          data-testid="dependency-form-back"
        >
          &larr; {{ t('common.backTo', { page: t('dependency.dependencies.title').toLowerCase() }) }}
        </RouterLink>
      </div>

      <div class="max-w-lg rounded-xl border border-border bg-surface p-6">
        <h2 class="mb-6 text-2xl font-bold text-text">
          {{ isEditMode ? t('dependency.dependencies.editDependency') : t('dependency.dependencies.createDependency') }}
        </h2>

        <form
          data-testid="dependency-form"
          @submit.prevent="handleSubmit"
        >
          <div
            v-if="formError"
            class="mb-4 rounded-lg bg-danger/10 p-3 text-sm text-danger"
            role="alert"
            data-testid="dependency-form-error"
          >
            {{ formError }}
          </div>

          <div class="mb-4">
            <label
              for="name"
              class="mb-1 block text-sm font-medium text-text"
            >{{ t('dependency.dependencies.name') }}</label>
            <input
              id="name"
              v-model="name"
              type="text"
              required
              class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
              data-testid="dependency-form-name"
            >
          </div>

          <div class="mb-4">
            <label
              for="currentVersion"
              class="mb-1 block text-sm font-medium text-text"
            >{{ t('dependency.dependencies.currentVersion') }}</label>
            <input
              id="currentVersion"
              v-model="currentVersion"
              type="text"
              required
              class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
              data-testid="dependency-form-current-version"
            >
          </div>

          <div class="mb-4">
            <label
              for="latestVersion"
              class="mb-1 block text-sm font-medium text-text"
            >{{ t('dependency.dependencies.latestVersion') }}</label>
            <input
              id="latestVersion"
              v-model="latestVersion"
              type="text"
              required
              class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
              data-testid="dependency-form-latest-version"
            >
          </div>

          <div class="mb-4">
            <label
              for="ltsVersion"
              class="mb-1 block text-sm font-medium text-text"
            >{{ t('dependency.dependencies.ltsVersion') }}</label>
            <input
              id="ltsVersion"
              v-model="ltsVersion"
              type="text"
              required
              class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
              data-testid="dependency-form-lts-version"
            >
          </div>

          <div class="mb-4">
            <label
              for="packageManager"
              class="mb-1 block text-sm font-medium text-text"
            >{{ t('dependency.dependencies.packageManager') }}</label>
            <select
              id="packageManager"
              v-model="packageManager"
              required
              class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
              data-testid="dependency-form-package-manager"
            >
              <option value="composer">
                Composer
              </option>
              <option value="npm">
                npm
              </option>
              <option value="pip">
                pip
              </option>
            </select>
          </div>

          <div class="mb-4">
            <label
              for="type"
              class="mb-1 block text-sm font-medium text-text"
            >{{ t('dependency.dependencies.type') }}</label>
            <select
              id="type"
              v-model="type"
              required
              class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
              data-testid="dependency-form-type"
            >
              <option value="runtime">
                {{ t('dependency.dependencies.typeRuntime') }}
              </option>
              <option value="dev">
                {{ t('dependency.dependencies.typeDev') }}
              </option>
            </select>
          </div>

          <div class="mb-4 flex items-center gap-2">
            <input
              id="isOutdated"
              v-model="isOutdated"
              type="checkbox"
              class="rounded border-border"
              data-testid="dependency-form-is-outdated"
            >
            <label
              for="isOutdated"
              class="text-sm font-medium text-text"
            >{{ t('dependency.dependencies.isOutdated') }}</label>
          </div>

          <div class="mb-4">
            <label
              for="repositoryUrl"
              class="mb-1 block text-sm font-medium text-text"
            >{{ t('dependency.dependencies.repositoryUrl') }}</label>
            <input
              id="repositoryUrl"
              v-model="repositoryUrl"
              type="url"
              placeholder="https://github.com/vendor/package"
              class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
              data-testid="dependency-form-repository-url"
            >
          </div>

          <div
            v-if="!isEditMode"
            class="mb-6"
          >
            <label
              for="projectId"
              class="mb-1 block text-sm font-medium text-text"
            >{{ t('dependency.dependencies.projectId') }}</label>
            <input
              id="projectId"
              v-model="projectId"
              type="text"
              required
              class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
              data-testid="dependency-form-project-id"
            >
          </div>

          <button
            type="submit"
            :disabled="submitting"
            class="w-full rounded-lg bg-primary px-4 py-2.5 font-medium text-white transition-colors hover:bg-primary-dark disabled:opacity-50"
            data-testid="dependency-form-submit"
          >
            {{ submitting ? t('common.saving') : (isEditMode ? t('dependency.dependencies.updateDependency') : t('dependency.dependencies.createDependency')) }}
          </button>
        </form>
      </div>
    </div>
  </DashboardLayout>
</template>
