<script setup lang="ts">
import { useI18n } from 'vue-i18n';

import type { GroupBy, ViewMode } from '@/catalog/composables/useTechStackGrouping';

const { t } = useI18n();

defineProps<{
  availableFrameworks: string[];
  availableLanguages: string[];
  availableProviders: { id: string; name: string }[];
  viewMode: ViewMode;
}>();

const search = defineModel<string>('search', { required: true });
const filterFramework = defineModel<string>('filterFramework', { required: true });
const filterLanguage = defineModel<string>('filterLanguage', { required: true });
const filterProvider = defineModel<string>('filterProvider', { required: true });
const filterStatus = defineModel<string>('filterStatus', { required: true });
const groupBy = defineModel<GroupBy>('groupBy', { required: true });
</script>

<template>
  <!-- Filters -->
  <div class="mb-4 flex flex-wrap items-center gap-3" data-testid="tech-stack-filters">
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
        :aria-label="
          viewMode === 'languages'
            ? t('catalog.techStacks.searchLanguagePlaceholder')
            : t('catalog.techStacks.searchPlaceholder')
        "
        :placeholder="
          viewMode === 'languages'
            ? t('catalog.techStacks.searchLanguagePlaceholder')
            : t('catalog.techStacks.searchPlaceholder')
        "
        class="w-full rounded-lg border border-border bg-surface py-2 pl-9 pr-3 text-sm text-text placeholder:text-text-muted focus:border-primary focus:outline-none"
        data-testid="tech-stack-search"
      />
    </div>
    <select
      v-if="viewMode === 'languages'"
      v-model="filterLanguage"
      :aria-label="t('catalog.techStacks.allLanguages')"
      class="rounded-lg border border-border bg-surface px-3 py-2 text-sm text-text focus:border-primary focus:outline-none"
      data-testid="tech-stack-filter-language"
    >
      <option value="">
        {{ t('catalog.techStacks.allLanguages') }}
      </option>
      <option v-for="lang in availableLanguages" :key="lang" :value="lang">
        {{ lang }}
      </option>
    </select>
    <select
      v-else
      v-model="filterFramework"
      :aria-label="t('catalog.techStacks.allFrameworks')"
      class="rounded-lg border border-border bg-surface px-3 py-2 text-sm text-text focus:border-primary focus:outline-none"
      data-testid="tech-stack-filter-framework"
    >
      <option value="">
        {{ t('catalog.techStacks.allFrameworks') }}
      </option>
      <option v-for="fw in availableFrameworks" :key="fw" :value="fw">
        {{ fw }}
      </option>
    </select>
    <select
      v-model="filterProvider"
      :aria-label="t('catalog.techStacks.allProviders')"
      class="rounded-lg border border-border bg-surface px-3 py-2 text-sm text-text focus:border-primary focus:outline-none"
      data-testid="tech-stack-filter-provider"
    >
      <option value="">
        {{ t('catalog.techStacks.allProviders') }}
      </option>
      <option v-for="prov in availableProviders" :key="prov.id" :value="prov.id">
        {{ prov.name }}
      </option>
    </select>
    <select
      v-model="filterStatus"
      :aria-label="t('catalog.techStacks.allStatuses')"
      class="rounded-lg border border-border bg-surface px-3 py-2 text-sm text-text focus:border-primary focus:outline-none"
      data-testid="tech-stack-filter-status"
    >
      <option value="">
        {{ t('catalog.techStacks.allStatuses') }}
      </option>
      <option value="active">
        {{ t('catalog.techStacks.statusActive') }}
      </option>
      <option value="eol">
        {{ t('catalog.techStacks.statusUnmaintained') }}
      </option>
      <option value="warning">
        {{ t('catalog.techStacks.statusInactive') }}
      </option>
    </select>
  </div>

  <!-- Group toggle -->
  <div class="mb-4 flex items-center gap-1" data-testid="tech-stack-group-toggle">
    <button
      v-for="mode in viewMode === 'languages'
        ? (['project', 'language', 'provider'] as const)
        : (['project', 'framework', 'provider'] as const)"
      :key="mode"
      :class="
        groupBy === mode
          ? 'border-primary bg-primary/10 text-primary'
          : 'border-border text-text-muted hover:border-primary/50'
      "
      class="rounded-lg border px-3 py-1.5 text-xs font-medium transition-colors"
      @click="groupBy = mode"
    >
      {{ t(`catalog.techStacks.groupBy${mode.charAt(0).toUpperCase() + mode.slice(1)}`) }}
    </button>
  </div>
</template>
