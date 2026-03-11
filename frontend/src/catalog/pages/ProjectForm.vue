<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, useRoute, useRouter } from 'vue-router'

import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'
import { useProjectStore } from '@/catalog/stores/project'
import type { ProjectVisibility } from '@/catalog/types/project'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const projectStore = useProjectStore()

const projectId = computed(() => route.params.id as string | undefined)
const isEditMode = computed(() => !!projectId.value)

const name = ref('')
const slug = ref('')
const description = ref('')
const repositoryUrl = ref('')
const defaultBranch = ref('main')
const visibility = ref<ProjectVisibility>('private')
const ownerId = ref('')
const submitting = ref(false)
const formError = ref('')

onMounted(async () => {
  if (isEditMode.value && projectId.value) {
    await projectStore.fetchOne(projectId.value)
    if (projectStore.selected) {
      name.value = projectStore.selected.name
      slug.value = projectStore.selected.slug
      description.value = projectStore.selected.description ?? ''
      repositoryUrl.value = projectStore.selected.repositoryUrl
      defaultBranch.value = projectStore.selected.defaultBranch
      visibility.value = projectStore.selected.visibility
      ownerId.value = projectStore.selected.ownerId
    }
  }
})

async function handleSubmit() {
  formError.value = ''
  submitting.value = true

  try {
    if (isEditMode.value && projectId.value) {
      await projectStore.update(projectId.value, {
        name: name.value,
        slug: slug.value,
        description: description.value || undefined,
        repositoryUrl: repositoryUrl.value,
        defaultBranch: defaultBranch.value,
        visibility: visibility.value,
      })
      router.push({ name: 'catalog-projects-detail', params: { id: projectId.value } })
    } else {
      const project = await projectStore.create({
        name: name.value,
        slug: slug.value,
        description: description.value || undefined,
        repositoryUrl: repositoryUrl.value,
        defaultBranch: defaultBranch.value,
        visibility: visibility.value,
        ownerId: ownerId.value,
      })
      router.push({ name: 'catalog-projects-detail', params: { id: project.id } })
    }
  } catch {
    formError.value = isEditMode.value ? t('catalog.projects.updateFailed') : t('catalog.projects.createFailed')
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <DashboardLayout>
    <div data-testid="project-form-page">
      <div class="mb-6">
        <RouterLink
          :to="{ name: 'catalog-projects-list' }"
          class="text-sm text-primary hover:text-primary-dark"
          data-testid="project-form-back"
        >
          &larr; {{ t('common.backTo', { page: t('catalog.projects.title').toLowerCase() }) }}
        </RouterLink>
      </div>

      <div class="max-w-lg rounded-xl border border-border bg-surface p-6">
        <h2 class="mb-6 text-2xl font-bold text-text">
          {{ isEditMode ? t('catalog.projects.editProject') : t('catalog.projects.createProject') }}
        </h2>

        <form
          data-testid="project-form"
          @submit.prevent="handleSubmit"
        >
          <div
            v-if="formError"
            class="mb-4 rounded-lg bg-danger/10 p-3 text-sm text-danger"
            role="alert"
            data-testid="project-form-error"
          >
            {{ formError }}
          </div>

          <div class="mb-4">
            <label
              for="name"
              class="mb-1 block text-sm font-medium text-text"
            >{{ t('catalog.projects.name') }}</label>
            <input
              id="name"
              v-model="name"
              type="text"
              required
              class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
              data-testid="project-form-name"
            >
          </div>

          <div class="mb-4">
            <label
              for="slug"
              class="mb-1 block text-sm font-medium text-text"
            >{{ t('catalog.projects.slug') }}</label>
            <input
              id="slug"
              v-model="slug"
              type="text"
              required
              pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
              class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
              data-testid="project-form-slug"
            >
          </div>

          <div class="mb-4">
            <label
              for="description"
              class="mb-1 block text-sm font-medium text-text"
            >{{ t('catalog.projects.description') }}</label>
            <textarea
              id="description"
              v-model="description"
              rows="3"
              class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
              data-testid="project-form-description"
            />
          </div>

          <div class="mb-4">
            <label
              for="repositoryUrl"
              class="mb-1 block text-sm font-medium text-text"
            >{{ t('catalog.projects.repositoryUrl') }}</label>
            <input
              id="repositoryUrl"
              v-model="repositoryUrl"
              type="url"
              required
              class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
              data-testid="project-form-repository-url"
            >
          </div>

          <div class="mb-4">
            <label
              for="defaultBranch"
              class="mb-1 block text-sm font-medium text-text"
            >{{ t('catalog.projects.defaultBranch') }}</label>
            <input
              id="defaultBranch"
              v-model="defaultBranch"
              type="text"
              required
              class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
              data-testid="project-form-default-branch"
            >
          </div>

          <div class="mb-4">
            <label
              for="visibility"
              class="mb-1 block text-sm font-medium text-text"
            >{{ t('catalog.projects.visibility') }}</label>
            <select
              id="visibility"
              v-model="visibility"
              required
              class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
              data-testid="project-form-visibility"
            >
              <option value="private">
                {{ t('catalog.projects.visibilityPrivate') }}
              </option>
              <option value="public">
                {{ t('catalog.projects.visibilityPublic') }}
              </option>
            </select>
          </div>

          <div
            v-if="!isEditMode"
            class="mb-6"
          >
            <label
              for="ownerId"
              class="mb-1 block text-sm font-medium text-text"
            >{{ t('catalog.projects.ownerId') }}</label>
            <input
              id="ownerId"
              v-model="ownerId"
              type="text"
              required
              class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
              data-testid="project-form-owner-id"
            >
          </div>

          <button
            type="submit"
            :disabled="submitting"
            class="w-full rounded-lg bg-primary px-4 py-2.5 font-medium text-white transition-colors hover:bg-primary-dark disabled:opacity-50"
            data-testid="project-form-submit"
          >
            {{ submitting ? t('common.saving') : (isEditMode ? t('catalog.projects.updateProject') : t('catalog.projects.createProject')) }}
          </button>
        </form>
      </div>
    </div>
  </DashboardLayout>
</template>
