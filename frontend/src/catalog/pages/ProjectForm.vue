<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, useRoute, useRouter } from 'vue-router'

import type { ProjectVisibility } from '@/catalog/types/project'

import { useProjectStore } from '@/catalog/stores/project'
import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'

const route = useRoute()
const router = useRouter()
const { d, t } = useI18n()
const projectStore = useProjectStore()

const projectId = computed(() => route.params.id as string | undefined)
const isEdit = computed(() => !!projectId.value)

const form = reactive({
  defaultBranch: 'main',
  description: '',
  name: '',
  ownerId: '',
  repositoryUrl: '',
  slug: '',
  visibility: 'private' as ProjectVisibility,
})
const touched = reactive({
  defaultBranch: false,
  name: false,
  ownerId: false,
  repositoryUrl: false,
  slug: false,
})
const slugManuallyEdited = ref(false)
const submitting = ref(false)
const formError = ref('')
const loadingProject = ref(false)

const errors = computed(() => ({
  defaultBranch: touched.defaultBranch && !form.defaultBranch.trim()
    ? t('common.validation.required')
    : '',
  name: touched.name && !form.name.trim()
    ? t('common.validation.required')
    : '',
  ownerId: touched.ownerId && !isEdit.value && !form.ownerId.trim()
    ? t('common.validation.required')
    : '',
  repositoryUrl: touched.repositoryUrl && !form.repositoryUrl.trim()
    ? t('common.validation.required')
    : touched.repositoryUrl && !isValidUrl(form.repositoryUrl)
      ? t('common.validation.invalidUrl')
      : '',
  slug: touched.slug && !form.slug.trim()
    ? t('common.validation.required')
    : touched.slug && !/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(form.slug)
      ? t('catalog.projects.slugHint')
      : '',
}))

const hasErrors = computed(() => Object.values(errors.value).some(Boolean))

function isValidUrl(url: string): boolean {
  try {
    new URL(url)
    return true
  } catch {
    return false
  }
}

function toSlug(value: string): string {
  return value
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-|-$/g, '')
}

function touchAll() {
  touched.name = true
  touched.slug = true
  touched.repositoryUrl = true
  touched.defaultBranch = true
  touched.ownerId = true
}

watch(() => form.name, (val) => {
  if (!slugManuallyEdited.value && !isEdit.value) {
    form.slug = toSlug(val)
  }
})

function handleSlugInput() {
  slugManuallyEdited.value = true
}

onMounted(async () => {
  if (isEdit.value && projectId.value) {
    loadingProject.value = true
    await projectStore.fetchOne(projectId.value)
    if (projectStore.selected) {
      form.name = projectStore.selected.name
      form.slug = projectStore.selected.slug
      form.description = projectStore.selected.description ?? ''
      form.repositoryUrl = projectStore.selected.repositoryUrl
      form.defaultBranch = projectStore.selected.defaultBranch
      form.visibility = projectStore.selected.visibility
      form.ownerId = projectStore.selected.ownerId
      slugManuallyEdited.value = true
    }
    loadingProject.value = false
  }
})

async function handleSubmit() {
  touchAll()
  if (hasErrors.value) return

  formError.value = ''
  submitting.value = true

  try {
    if (isEdit.value && projectId.value) {
      await projectStore.update(projectId.value, {
        defaultBranch: form.defaultBranch,
        description: form.description || undefined,
        name: form.name,
        repositoryUrl: form.repositoryUrl,
        slug: form.slug,
        visibility: form.visibility,
      })
      router.push({ name: 'catalog-projects-detail', params: { id: projectId.value } })
    } else {
      const project = await projectStore.create({
        defaultBranch: form.defaultBranch,
        description: form.description || undefined,
        name: form.name,
        ownerId: form.ownerId,
        repositoryUrl: form.repositoryUrl,
        slug: form.slug,
        visibility: form.visibility,
      })
      router.push({ name: 'catalog-projects-detail', params: { id: project.id } })
    }
  } catch {
    formError.value = isEdit.value ? t('catalog.projects.updateFailed') : t('catalog.projects.createFailed')
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <DashboardLayout>
    <div data-testid="project-form-page">
      <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-2 text-sm text-text-muted">
          <RouterLink
            :to="{ name: 'catalog-projects-list' }"
            class="text-primary hover:text-primary-dark"
            data-testid="project-form-back"
          >
            {{ t('catalog.projects.title') }}
          </RouterLink>
          <span>/</span>
          <span
            v-if="isEdit && projectStore.selected"
            class="flex items-center gap-1.5"
          >
            <RouterLink
              :to="{ name: 'catalog-projects-detail', params: { id: projectId } }"
              class="text-primary hover:text-primary-dark"
            >
              {{ projectStore.selected.name }}
            </RouterLink>
            <span>/</span>
            <span class="text-text">{{ t('common.actions.edit') }}</span>
          </span>
          <span
            v-else
            class="text-text"
          >{{ t('catalog.projects.createProject') }}</span>
        </div>
      </div>

      <div
        v-if="loadingProject"
        class="py-8 text-center text-text-muted"
        data-testid="project-form-loading"
      >
        {{ t('common.actions.loading') }}
      </div>

      <div
        v-else
        class="grid grid-cols-1 gap-6 lg:grid-cols-3"
      >
        <div class="lg:col-span-2">
          <div class="rounded-xl border border-border bg-surface p-6">
            <h2 class="mb-6 text-xl font-bold text-text">
              {{ isEdit ? t('catalog.projects.editProject') : t('catalog.projects.createProject') }}
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

              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                  <label
                    for="field-name"
                    class="mb-1 block text-sm font-medium text-text"
                  >{{ t('catalog.projects.name') }} <span class="text-danger">*</span></label>
                  <input
                    id="field-name"
                    v-model="form.name"
                    type="text"
                    required
                    :class="errors.name ? 'border-danger focus:border-danger focus:ring-danger/20' : 'border-border focus:border-primary focus:ring-primary/20'"
                    class="w-full rounded-lg border px-3 py-2 text-text focus:ring-2 focus:outline-none"
                    data-testid="project-form-name"
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
                    for="field-slug"
                    class="mb-1 block text-sm font-medium text-text"
                  >{{ t('catalog.projects.slug') }} <span class="text-danger">*</span></label>
                  <input
                    id="field-slug"
                    v-model="form.slug"
                    type="text"
                    required
                    :class="errors.slug ? 'border-danger focus:border-danger focus:ring-danger/20' : 'border-border focus:border-primary focus:ring-primary/20'"
                    class="w-full rounded-lg border px-3 py-2 text-text focus:ring-2 focus:outline-none"
                    data-testid="project-form-slug"
                    @blur="touched.slug = true"
                    @input="handleSlugInput"
                  >
                  <p
                    v-if="errors.slug"
                    class="mt-1 text-xs text-danger"
                    data-testid="error-slug"
                  >
                    {{ errors.slug }}
                  </p>
                  <p
                    v-else
                    class="mt-1 text-xs text-text-muted"
                  >
                    {{ t('catalog.projects.slugHint') }}
                  </p>
                </div>
              </div>

              <div class="mt-4">
                <label
                  for="field-description"
                  class="mb-1 block text-sm font-medium text-text"
                >{{ t('catalog.projects.description') }}</label>
                <textarea
                  id="field-description"
                  v-model="form.description"
                  rows="3"
                  class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
                  data-testid="project-form-description"
                />
              </div>

              <div class="mt-4">
                <label
                  for="field-repositoryUrl"
                  class="mb-1 block text-sm font-medium text-text"
                >{{ t('catalog.projects.repositoryUrl') }} <span class="text-danger">*</span></label>
                <input
                  id="field-repositoryUrl"
                  v-model="form.repositoryUrl"
                  type="url"
                  required
                  placeholder="https://github.com/org/repo"
                  :class="errors.repositoryUrl ? 'border-danger focus:border-danger focus:ring-danger/20' : 'border-border focus:border-primary focus:ring-primary/20'"
                  class="w-full rounded-lg border px-3 py-2 text-text focus:ring-2 focus:outline-none"
                  data-testid="project-form-repository-url"
                  @blur="touched.repositoryUrl = true"
                >
                <p
                  v-if="errors.repositoryUrl"
                  class="mt-1 text-xs text-danger"
                  data-testid="error-repositoryUrl"
                >
                  {{ errors.repositoryUrl }}
                </p>
                <p
                  v-else
                  class="mt-1 text-xs text-text-muted"
                >
                  {{ t('catalog.projects.repositoryUrlHint') }}
                </p>
              </div>

              <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                  <label
                    for="field-defaultBranch"
                    class="mb-1 block text-sm font-medium text-text"
                  >{{ t('catalog.projects.defaultBranch') }} <span class="text-danger">*</span></label>
                  <input
                    id="field-defaultBranch"
                    v-model="form.defaultBranch"
                    type="text"
                    required
                    :class="errors.defaultBranch ? 'border-danger focus:border-danger focus:ring-danger/20' : 'border-border focus:border-primary focus:ring-primary/20'"
                    class="w-full rounded-lg border px-3 py-2 text-text focus:ring-2 focus:outline-none"
                    data-testid="project-form-default-branch"
                    @blur="touched.defaultBranch = true"
                  >
                  <p
                    v-if="errors.defaultBranch"
                    class="mt-1 text-xs text-danger"
                    data-testid="error-defaultBranch"
                  >
                    {{ errors.defaultBranch }}
                  </p>
                </div>

                <div>
                  <label
                    for="field-visibility"
                    class="mb-1 block text-sm font-medium text-text"
                  >{{ t('catalog.projects.visibility') }}</label>
                  <select
                    id="field-visibility"
                    v-model="form.visibility"
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
              </div>

              <div
                v-if="!isEdit"
                class="mt-4"
              >
                <label
                  for="field-ownerId"
                  class="mb-1 block text-sm font-medium text-text"
                >{{ t('catalog.projects.ownerId') }} <span class="text-danger">*</span></label>
                <input
                  id="field-ownerId"
                  v-model="form.ownerId"
                  type="text"
                  required
                  :class="errors.ownerId ? 'border-danger focus:border-danger focus:ring-danger/20' : 'border-border focus:border-primary focus:ring-primary/20'"
                  class="w-full rounded-lg border px-3 py-2 text-text focus:ring-2 focus:outline-none"
                  data-testid="project-form-owner-id"
                  @blur="touched.ownerId = true"
                >
                <p
                  v-if="errors.ownerId"
                  class="mt-1 text-xs text-danger"
                  data-testid="error-ownerId"
                >
                  {{ errors.ownerId }}
                </p>
              </div>

              <div class="mt-6 flex items-center gap-3">
                <button
                  type="submit"
                  :disabled="submitting"
                  class="rounded-lg bg-primary px-6 py-2.5 font-medium text-white transition-colors hover:bg-primary-dark disabled:opacity-50"
                  data-testid="project-form-submit"
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
                    {{ isEdit ? t('catalog.projects.updateProject') : t('catalog.projects.createProject') }}
                  </span>
                </button>
                <RouterLink
                  :to="isEdit && projectId ? { name: 'catalog-projects-detail', params: { id: projectId } } : { name: 'catalog-projects-list' }"
                  class="rounded-lg border border-border px-6 py-2.5 font-medium text-text transition-colors hover:bg-surface-muted"
                  data-testid="project-form-cancel"
                >
                  {{ t('common.actions.cancel') }}
                </RouterLink>
              </div>
            </form>
          </div>
        </div>

        <div
          v-if="isEdit && projectStore.selected"
          class="space-y-4"
        >
          <div
            class="rounded-xl border border-border bg-surface p-5"
            data-testid="project-sidebar-info"
          >
            <h3 class="mb-4 text-sm font-semibold text-text">
              {{ t('catalog.projects.projectInfo') }}
            </h3>

            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-sm text-text-muted">{{ t('catalog.projects.visibility') }}</span>
                <span
                  :class="[
                    'rounded-full px-2 py-0.5 text-xs font-medium',
                    projectStore.selected.visibility === 'public'
                      ? 'bg-success/10 text-success'
                      : 'bg-warning/10 text-warning',
                  ]"
                  data-testid="sidebar-visibility"
                >
                  {{ projectStore.selected.visibility }}
                </span>
              </div>

              <div class="flex items-center justify-between">
                <span class="text-sm text-text-muted">{{ t('catalog.projects.techStacks') }}</span>
                <span
                  class="text-sm font-semibold tabular-nums text-text"
                  data-testid="sidebar-stacks"
                >
                  {{ projectStore.selected.techStacksCount }}
                </span>
              </div>

              <div class="flex items-center justify-between">
                <span class="text-sm text-text-muted">{{ t('common.createdAt') }}</span>
                <span class="text-sm text-text">
                  {{ d(new Date(projectStore.selected.createdAt), 'short') }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
