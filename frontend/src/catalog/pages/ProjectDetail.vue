<script setup lang="ts">
import { computed, onMounted, ref, type Ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink, useRoute, useRouter } from 'vue-router';

import ProjectDependenciesTab from '@/catalog/components/ProjectDependenciesTab.vue';
import ProjectFrameworksTab from '@/catalog/components/ProjectFrameworksTab.vue';
import ProjectLanguagesTab from '@/catalog/components/ProjectLanguagesTab.vue';
import { useFrameworkStore } from '@/catalog/stores/framework';
import { useProjectStore } from '@/catalog/stores/project';
import { useDependencyStore } from '@/dependency/stores/dependency';
import ConfirmDialog from '@/shared/components/ConfirmDialog.vue';
import { useGlobalSync } from '@/shared/composables/useGlobalSync';
import DashboardLayout from '@/shared/layouts/DashboardLayout.vue';
import { useToastStore } from '@/shared/stores/toast';

const PER_PAGE = 20;

const route = useRoute();
const router = useRouter();
const showUnfollow = ref(false);
const { t } = useI18n();
const projectStore = useProjectStore();
const dependencyStore = useDependencyStore();
const toastStore = useToastStore();

const activeTab = ref<'dependencies' | 'frameworks' | 'languages'>('languages');
const projectId = computed(() => route.params.id as string);
const frameworkStore = useFrameworkStore();
const { isRunning, onStepCompleted, startSync } = useGlobalSync();

onStepCompleted((step) => {
  if (step === 'sync_projects') {
    projectStore.fetchOne(projectId.value);
    frameworkStore.fetchAll(1, 1000, projectId.value);
  }
  if (step === 'sync_versions') {
    dependencyStore.fetchAll(1, PER_PAGE, projectId.value);
  }
});

const scanFreshness = computed(() => {
  if (!projectStore.selected?.updatedAt) return 'stale';
  const diff = Date.now() - new Date(projectStore.selected.updatedAt).getTime();
  const hours = diff / (1000 * 60 * 60);
  if (hours < 1) return 'fresh';
  if (hours < 24) return 'recent';
  return 'stale';
});

function truncateUrl(url: string, max = 50): string {
  if (url.length <= max) return url;
  return `${url.slice(0, max)}…`;
}

const branchLoading: Ref<boolean> = ref(false);

onMounted(async () => {
  await projectStore.fetchOne(projectId.value);
  branchLoading.value = true;
  await projectStore.fetchBranches(projectId.value);
  branchLoading.value = false;
});

async function handleBranchChange(event: Event) {
  const newBranch = (event.target as HTMLSelectElement).value;
  await projectStore.update(projectId.value, { defaultBranch: newBranch });
  toastStore.addToast({
    title: t('catalog.projects.branchChanged', { branch: newBranch }),
    variant: 'success',
  });
}

async function handleScan() {
  await startSync(projectId.value);
}
</script>

<template>
  <DashboardLayout>
    <div data-testid="project-detail-page">
      <nav
        class="mb-6 flex items-center gap-1 text-sm text-text-muted"
        data-testid="project-detail-breadcrumb"
      >
        <RouterLink
          :to="{ name: 'catalog-projects-list' }"
          class="text-primary hover:text-primary-dark"
        >
          {{ t('catalog.projects.title') }}
        </RouterLink>
        <span>/</span>
        <span v-if="projectStore.selected" class="font-medium text-text">
          {{ projectStore.selected.name }}
        </span>
      </nav>

      <div
        v-if="projectStore.loading"
        class="py-8 text-center text-text-muted"
        data-testid="project-detail-loading"
      >
        {{ t('common.actions.loading') }}
      </div>

      <div
        v-else-if="projectStore.error"
        class="rounded-lg bg-danger/10 p-4 text-danger"
        role="alert"
        data-testid="project-detail-error"
      >
        {{ projectStore.error }}
      </div>

      <template v-else-if="projectStore.selected">
        <div class="mb-6 flex items-start justify-between">
          <div>
            <h2 class="text-2xl font-bold text-text">
              {{ projectStore.selected.name }}
            </h2>
            <p
              v-if="projectStore.selected.description"
              class="mt-1 text-sm text-text-muted"
              data-testid="project-detail-description"
            >
              {{ projectStore.selected.description }}
            </p>
            <p class="mt-1 text-sm text-text-muted">
              <a
                :href="projectStore.selected.repositoryUrl"
                target="_blank"
                rel="noopener"
                class="text-primary hover:text-primary-dark"
                data-testid="project-detail-repository-url"
                :title="projectStore.selected.repositoryUrl"
                >{{ truncateUrl(projectStore.selected.repositoryUrl) }} ↗</a
              >
            </p>
            <div class="mt-2 flex items-center gap-2">
              <svg
                class="h-4 w-4 text-text-muted"
                fill="none"
                stroke="currentColor"
                stroke-width="1.5"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m9.86-2.374a4.5 4.5 0 00-1.242-7.244l4.5-4.5a4.5 4.5 0 016.364 6.364l-1.757 1.757"
                />
              </svg>
              <select
                :value="projectStore.selected.defaultBranch"
                :disabled="projectStore.branches.length === 0"
                :aria-label="t('catalog.projects.defaultBranch')"
                class="rounded-lg border border-border bg-surface px-2 py-1 text-sm text-text focus:border-primary focus:outline-none"
                data-testid="project-branch-select"
                @change="handleBranchChange"
              >
                <option v-for="branch in projectStore.branches" :key="branch" :value="branch">
                  {{ branch }}
                </option>
              </select>
            </div>
          </div>
          <div class="flex gap-2">
            <button
              v-if="projectStore.selected.externalId"
              :disabled="isRunning"
              class="rounded-lg border border-primary bg-transparent px-4 py-2 text-sm font-medium text-primary transition-colors hover:bg-primary hover:text-white disabled:opacity-50"
              data-testid="project-scan-btn"
              @click="handleScan"
            >
              {{ isRunning ? t('catalog.projects.scanning') : t('catalog.projects.scanProject') }}
            </button>
            <button
              class="rounded-lg border border-danger bg-transparent px-4 py-2 text-sm font-medium text-danger transition-colors hover:bg-danger hover:text-white"
              data-testid="project-unfollow-btn"
              @click="showUnfollow = true"
            >
              {{ t('catalog.projects.unfollow') }}
            </button>
          </div>
        </div>

        <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4" data-testid="project-stats-cards">
          <div class="rounded-xl border border-border bg-surface p-4 text-center">
            <div class="text-lg font-bold text-text">
              <span
                :class="[
                  'inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium',
                  projectStore.selected.visibility === 'public'
                    ? 'bg-success/10 text-success'
                    : 'bg-warning/10 text-warning',
                ]"
                data-testid="project-stat-visibility"
              >
                {{ projectStore.selected.visibility }}
              </span>
            </div>
            <p class="mt-1 text-xs text-text-muted">
              {{ t('catalog.projects.visibility') }}
            </p>
          </div>

          <div class="rounded-xl border border-border bg-surface p-4 text-left">
            <p class="mb-2 text-xs text-text-muted">
              {{ t('catalog.projects.frameworks') }}
            </p>
            <div
              v-if="frameworkStore.frameworks.length > 0"
              class="flex flex-wrap gap-2"
              data-testid="project-stat-frameworks"
            >
              <span
                v-for="fw in frameworkStore.frameworks"
                :key="fw.id"
                class="rounded-full bg-surface-muted px-2 py-0.5 text-xs font-medium text-text"
                >{{ fw.name }} {{ fw.version }}</span
              >
            </div>
            <p v-else class="text-sm text-text-muted" data-testid="project-stat-frameworks">—</p>
          </div>

          <div class="rounded-xl border border-border bg-surface p-4 text-center">
            <div
              :class="{
                'text-green-600': scanFreshness === 'fresh',
                'text-yellow-600': scanFreshness === 'recent',
                'text-red-600': scanFreshness === 'stale',
              }"
              class="text-lg font-bold"
              data-testid="project-stat-freshness"
            >
              {{ t(`catalog.projects.freshness.${scanFreshness}`) }}
            </div>
            <p class="mt-1 text-xs text-text-muted">
              {{ t('catalog.projects.lastScan') }}
            </p>
          </div>
        </div>

        <div class="mb-4 flex gap-2 border-b border-border">
          <button
            :class="[
              'px-4 py-2 text-sm font-medium transition-colors',
              activeTab === 'languages'
                ? 'border-b-2 border-primary text-primary'
                : 'text-text-muted hover:text-text',
            ]"
            data-testid="tab-languages"
            @click="activeTab = 'languages'"
          >
            {{ t('catalog.projects.languages') }}
          </button>
          <button
            :class="[
              'px-4 py-2 text-sm font-medium transition-colors',
              activeTab === 'frameworks'
                ? 'border-b-2 border-primary text-primary'
                : 'text-text-muted hover:text-text',
            ]"
            data-testid="tab-frameworks"
            @click="activeTab = 'frameworks'"
          >
            {{ t('catalog.projects.frameworks') }}
          </button>
          <button
            :class="[
              'px-4 py-2 text-sm font-medium transition-colors',
              activeTab === 'dependencies'
                ? 'border-b-2 border-primary text-primary'
                : 'text-text-muted hover:text-text',
            ]"
            data-testid="tab-dependencies"
            @click="activeTab = 'dependencies'"
          >
            {{ t('catalog.projects.dependenciesCount', { count: dependencyStore.total }) }}
          </button>
        </div>

        <ProjectLanguagesTab v-if="activeTab === 'languages'" :project-id="projectId" />
        <ProjectFrameworksTab v-if="activeTab === 'frameworks'" :project-id="projectId" />
        <ProjectDependenciesTab v-if="activeTab === 'dependencies'" :project-id="projectId" />
      </template>

      <ConfirmDialog
        :open="showUnfollow"
        :title="t('catalog.projects.unfollowTitle')"
        :message="
          t('catalog.projects.unfollowMessage', { name: projectStore.selected?.name ?? '' })
        "
        :confirm-label="t('catalog.projects.unfollow')"
        variant="danger"
        @confirm="
          async () => {
            showUnfollow = false;
            await projectStore.remove(projectId);
            router.push({ name: 'catalog-projects-list' });
          }
        "
        @cancel="showUnfollow = false"
      />
    </div>
  </DashboardLayout>
</template>
