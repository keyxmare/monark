<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink, useRouter } from 'vue-router';

import { useProjectStore } from '@/catalog/stores/project';
import { useTechStackStore } from '@/catalog/stores/tech-stack';
import DashboardLayout from '@/shared/layouts/DashboardLayout.vue';

const router = useRouter();
const { t } = useI18n();
const techStackStore = useTechStackStore();
const projectStore = useProjectStore();

const language = ref('');
const framework = ref('');
const version = ref('');
const detectedAt = ref(new Date().toISOString().slice(0, 16));
const projectId = ref('');
const submitting = ref(false);
const formError = ref('');

onMounted(() => {
  projectStore.fetchAll(1, 100);
});

async function handleSubmit() {
  formError.value = '';
  submitting.value = true;

  try {
    await techStackStore.create({
      detectedAt: new Date(detectedAt.value).toISOString(),
      framework: framework.value,
      frameworkVersion: '',
      language: language.value,
      projectId: projectId.value,
      version: version.value,
    });
    router.push({ name: 'catalog-tech-stacks-list' });
  } catch {
    formError.value = t('catalog.techStacks.createFailed');
  } finally {
    submitting.value = false;
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
          &larr; {{ t('common.backTo', { page: t('catalog.techStacks.title').toLowerCase() }) }}
        </RouterLink>
      </div>

      <div class="max-w-lg rounded-xl border border-border bg-surface p-6">
        <h2 class="mb-6 text-2xl font-bold text-text">
          {{ t('catalog.techStacks.addTechStack') }}
        </h2>

        <form data-testid="tech-stack-form" @submit.prevent="handleSubmit">
          <div
            v-if="formError"
            class="mb-4 rounded-lg bg-danger/10 p-3 text-sm text-danger"
            role="alert"
            data-testid="tech-stack-form-error"
          >
            {{ formError }}
          </div>

          <div class="mb-4">
            <label for="projectId" class="mb-1 block text-sm font-medium text-text">{{
              t('catalog.techStacks.project')
            }}</label>
            <select
              id="projectId"
              v-model="projectId"
              required
              class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
              data-testid="tech-stack-form-project"
            >
              <option value="" disabled>
                {{ t('catalog.techStacks.selectProject') }}
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
            <label for="language" class="mb-1 block text-sm font-medium text-text">{{
              t('catalog.techStacks.language')
            }}</label>
            <input
              id="language"
              v-model="language"
              type="text"
              required
              class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
              data-testid="tech-stack-form-language"
            />
          </div>

          <div class="mb-4">
            <label for="framework" class="mb-1 block text-sm font-medium text-text">{{
              t('catalog.techStacks.framework')
            }}</label>
            <input
              id="framework"
              v-model="framework"
              type="text"
              required
              class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
              data-testid="tech-stack-form-framework"
            />
          </div>

          <div class="mb-4">
            <label for="version" class="mb-1 block text-sm font-medium text-text">{{
              t('catalog.techStacks.version')
            }}</label>
            <input
              id="version"
              v-model="version"
              type="text"
              required
              class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
              data-testid="tech-stack-form-version"
            />
          </div>

          <div class="mb-6">
            <label for="detectedAt" class="mb-1 block text-sm font-medium text-text">{{
              t('catalog.techStacks.detectedAt')
            }}</label>
            <input
              id="detectedAt"
              v-model="detectedAt"
              type="datetime-local"
              required
              class="w-full rounded-lg border border-border px-3 py-2 text-text focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
              data-testid="tech-stack-form-detected-at"
            />
          </div>

          <button
            type="submit"
            :disabled="submitting"
            class="w-full rounded-lg bg-primary px-4 py-2.5 font-medium text-white transition-colors hover:bg-primary-dark disabled:opacity-50"
            data-testid="tech-stack-form-submit"
          >
            {{ submitting ? t('common.saving') : t('catalog.techStacks.addTechStack') }}
          </button>
        </form>
      </div>
    </div>
  </DashboardLayout>
</template>
