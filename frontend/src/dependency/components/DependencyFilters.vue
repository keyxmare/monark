<script setup lang="ts">
import { useI18n } from 'vue-i18n';

import { useProjectStore } from '@/catalog/stores/project';

const { t } = useI18n();
const projectStore = useProjectStore();

const search = defineModel<string>('search', { required: true });
const filterPm = defineModel<string>('filterPm', { required: true });
const filterType = defineModel<string>('filterType', { required: true });
const filterStatus = defineModel<string>('filterStatus', { required: true });
const filterProject = defineModel<string>('filterProject', { required: true });
</script>

<template>
  <div class="mb-4 flex flex-wrap items-center gap-3">
    <div class="relative flex-1">
      <svg
        class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-text-muted"
        fill="none"
        stroke="currentColor"
        stroke-width="1.5"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"
        />
      </svg>
      <input
        v-model="search"
        type="search"
        :aria-label="t('common.actions.search')"
        :placeholder="t('common.actions.search')"
        class="w-full rounded-lg border border-border bg-surface py-2 pl-9 pr-3 text-sm text-text placeholder:text-text-muted focus:border-primary focus:outline-none"
      />
    </div>
    <select
      v-model="filterPm"
      :aria-label="t('catalog.projects.allPackageManagers')"
      class="rounded-lg border border-border bg-surface px-3 py-2 text-sm text-text focus:border-primary focus:outline-none"
    >
      <option value="">
        {{ t('catalog.projects.allPackageManagers') }}
      </option>
      <option value="composer">Composer</option>
      <option value="npm">npm</option>
      <option value="pip">pip</option>
    </select>
    <select
      v-model="filterType"
      :aria-label="t('catalog.projects.allTypes')"
      class="rounded-lg border border-border bg-surface px-3 py-2 text-sm text-text focus:border-primary focus:outline-none"
    >
      <option value="">
        {{ t('catalog.projects.allTypes') }}
      </option>
      <option value="runtime">
        {{ t('dependency.dependencies.typeRuntime') }}
      </option>
      <option value="dev">
        {{ t('dependency.dependencies.typeDev') }}
      </option>
    </select>
    <select
      v-model="filterStatus"
      :aria-label="t('catalog.techStacks.allStatuses')"
      class="rounded-lg border border-border bg-surface px-3 py-2 text-sm text-text focus:border-primary focus:outline-none"
    >
      <option value="">
        {{ t('catalog.techStacks.allStatuses') }}
      </option>
      <option value="outdated">
        {{ t('dependency.dependencies.outdated') }}
      </option>
      <option value="uptodate">
        {{ t('dependency.dependencies.upToDate') }}
      </option>
    </select>
    <select
      v-model="filterProject"
      :aria-label="t('dependency.dependencies.allProjects')"
      class="rounded-lg border border-border bg-surface px-3 py-2 text-sm text-text focus:border-primary focus:outline-none"
    >
      <option value="">
        {{ t('dependency.dependencies.allProjects') }}
      </option>
      <option v-for="p in projectStore.projects" :key="p.id" :value="p.id">
        {{ p.name }}
      </option>
    </select>
  </div>
</template>
