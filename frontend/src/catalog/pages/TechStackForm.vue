<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'

import DashboardLayout from '@/shared/layouts/DashboardLayout.vue'
import { useTechStackStore } from '@/catalog/stores/tech-stack'
import { useProjectStore } from '@/catalog/stores/project'

const router = useRouter()
const techStackStore = useTechStackStore()
const projectStore = useProjectStore()

const language = ref('')
const framework = ref('')
const version = ref('')
const detectedAt = ref(new Date().toISOString().slice(0, 16))
const projectId = ref('')
const submitting = ref(false)
const formError = ref('')

onMounted(() => {
  projectStore.fetchAll(1, 100)
})

async function handleSubmit() {
  formError.value = ''
  submitting.value = true

  try {
    await techStackStore.create({
      language: language.value,
      framework: framework.value,
      version: version.value,
      detectedAt: new Date(detectedAt.value).toISOString(),
      projectId: projectId.value,
    })
    router.push({ name: 'catalog-tech-stacks-list' })
  } catch {
    formError.value = 'Failed to create tech stack'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <DashboardLayout>
    <div data-testid="tech-stack-form-page">
      <div class="mb-6">
        <RouterLink
          :to="{ name: 'catalog-tech-stacks-list' }"
          class="text-sm text-primary hover:text-primary-dark"
          data-testid="tech-stack-form-back"
        >
          &larr; Back to tech stacks
        </RouterLink>
      </div>

      <div class="max-w-lg rounded-xl border border-border bg-surface p-6">
        <h2 class="mb-6 text-2xl font-bold text-text">
          Add Tech Stack
        </h2>

        <form
          data-testid="tech-stack-form"
          @submit.prevent="handleSubmit"
        >
          <div
            v-if="formError"
            class="mb-4 rounded-lg bg-danger/10 p-3 text-sm text-danger"
            role="alert"
            data-testid="tech-stack-form-error"
          >
            {{ formError }}
          </div>

          <div class="mb-4">
            <label
              for="projectId"
              class="mb-1 block text-sm font-medium text-text"
            >Project</label>
            <select
              id="projectId"
              v-model="projectId"
              required
              class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
              data-testid="tech-stack-form-project"
            >
              <option
                value=""
                disabled
              >
                Select a project
              </option>
              <option
                v-for="project in projectStore.projects"
                :key="project.id"
                :value="project.id"
              >
                {{ project.name }}
              </option>
            </select>
          </div>

          <div class="mb-4">
            <label
              for="language"
              class="mb-1 block text-sm font-medium text-text"
            >Language</label>
            <input
              id="language"
              v-model="language"
              type="text"
              required
              class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
              data-testid="tech-stack-form-language"
            >
          </div>

          <div class="mb-4">
            <label
              for="framework"
              class="mb-1 block text-sm font-medium text-text"
            >Framework</label>
            <input
              id="framework"
              v-model="framework"
              type="text"
              required
              class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
              data-testid="tech-stack-form-framework"
            >
          </div>

          <div class="mb-4">
            <label
              for="version"
              class="mb-1 block text-sm font-medium text-text"
            >Version</label>
            <input
              id="version"
              v-model="version"
              type="text"
              required
              class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
              data-testid="tech-stack-form-version"
            >
          </div>

          <div class="mb-6">
            <label
              for="detectedAt"
              class="mb-1 block text-sm font-medium text-text"
            >Detected At</label>
            <input
              id="detectedAt"
              v-model="detectedAt"
              type="datetime-local"
              required
              class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
              data-testid="tech-stack-form-detected-at"
            >
          </div>

          <button
            type="submit"
            :disabled="submitting"
            class="w-full rounded-lg bg-primary px-4 py-2.5 font-medium text-white transition-colors hover:bg-primary-dark disabled:opacity-50"
            data-testid="tech-stack-form-submit"
          >
            {{ submitting ? 'Saving...' : 'Add Tech Stack' }}
          </button>
        </form>
      </div>
    </div>
  </DashboardLayout>
</template>
