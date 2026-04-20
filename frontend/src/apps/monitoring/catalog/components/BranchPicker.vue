<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
  branches: string[];
  defaultBranch: string;
  loading?: boolean;
  modelValue: string;
}>();
const emit = defineEmits<{ 'update:modelValue': [string] }>();
const { t } = useI18n();

const open = ref(false);
const query = ref('');
const rootRef = ref<HTMLElement | null>(null);
const searchRef = ref<HTMLInputElement | null>(null);

const showSearch = computed(() => props.branches.length > 6);
const filtered = computed<string[]>(() => {
  if (!query.value.trim()) return props.branches;
  const q = query.value.trim().toLowerCase();
  return props.branches.filter((b) => b.toLowerCase().includes(q));
});

function onDocClick(e: MouseEvent): void {
  if (rootRef.value && !rootRef.value.contains(e.target as Node)) {
    open.value = false;
  }
}

function pick(branch: string): void {
  emit('update:modelValue', branch);
  open.value = false;
  query.value = '';
}

function toggle(): void {
  open.value = !open.value;
}

watch(open, (isOpen) => {
  if (isOpen) {
    document.addEventListener('mousedown', onDocClick);
    if (showSearch.value) {
      nextTick(() => searchRef.value?.focus());
    }
  } else {
    document.removeEventListener('mousedown', onDocClick);
    query.value = '';
  }
});

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', onDocClick);
});
</script>

<template>
  <div ref="rootRef" class="branch-picker">
    <button
      type="button"
      class="branch-trigger"
      :class="{ open }"
      :aria-expanded="open"
      :aria-haspopup="true"
      :title="t('monitoring.repos.detail.branch_picker_title')"
      @click="toggle"
    >
      <svg width="11" height="11" viewBox="0 0 12 12" fill="none" class="branch-icon">
        <circle cx="3" cy="2.5" r="1.4" stroke="currentColor" stroke-width="1.1" />
        <circle cx="3" cy="9.5" r="1.4" stroke="currentColor" stroke-width="1.1" />
        <circle cx="9" cy="6" r="1.4" stroke="currentColor" stroke-width="1.1" />
        <path
          d="M3 4v4M4.4 9a4 4 0 0 0 3.3-2.4"
          stroke="currentColor"
          stroke-width="1.1"
          fill="none"
          stroke-linecap="round"
        />
      </svg>
      <span class="branch-name">{{ modelValue }}</span>
      <span v-if="modelValue === defaultBranch" class="branch-default-hint">
        · {{ t('monitoring.repos.detail.branch_default_flag') }}
      </span>
      <span class="branch-caret" :class="{ open }" aria-hidden="true">
        <svg width="8" height="8" viewBox="0 0 8 8" fill="none">
          <path
            d="M2 3l2 2 2-2"
            stroke="currentColor"
            stroke-width="1.2"
            fill="none"
            stroke-linecap="round"
          />
        </svg>
      </span>
      <span class="branch-count">· {{ branches.length }} br.</span>
    </button>

    <div v-if="open" class="branch-popover" role="listbox">
      <div v-if="loading" class="branch-loading">{{ t('common.actions.loading') }}</div>
      <template v-else>
        <div v-if="showSearch" class="branch-search">
          <input
            ref="searchRef"
            v-model="query"
            type="search"
            :placeholder="t('monitoring.repos.detail.branch_search_placeholder')"
            :aria-label="t('monitoring.repos.detail.branch_search_placeholder')"
          />
        </div>
        <ul class="branch-list">
          <li
            v-for="b in filtered"
            :key="b"
            class="branch-item"
            :class="{ active: b === modelValue }"
            role="option"
            :aria-selected="b === modelValue"
            tabindex="0"
            @click="pick(b)"
            @keydown.enter.prevent="pick(b)"
            @keydown.space.prevent="pick(b)"
          >
            <span class="branch-item-name">{{ b }}</span>
            <span v-if="b === defaultBranch" class="branch-item-flag">
              {{ t('monitoring.repos.detail.branch_default_flag') }}
            </span>
          </li>
          <li v-if="filtered.length === 0" class="branch-item-empty">
            {{ t('monitoring.repos.detail.branch_empty', { query }) }}
          </li>
        </ul>
      </template>
    </div>
  </div>
</template>

<style scoped>
.branch-picker {
  position: relative;
  display: inline-flex;
}

.branch-trigger {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 10px 4px 8px;
  border: 1px solid var(--hub-line-strong);
  border-radius: 999px;
  background: color-mix(in oklab, var(--hub-bg-1) 80%, transparent);
  color: var(--hub-fg-0);
  font-family: var(--hub-font-mono);
  font-size: 11px;
  cursor: pointer;
  transition: background 160ms var(--hub-ease);
}
.branch-trigger:hover,
.branch-trigger.open {
  background: var(--hub-bg-3);
}
.branch-icon {
  flex-shrink: 0;
  color: var(--hub-fg-2);
}
.branch-name {
  color: var(--hub-fg-0);
}
.branch-default-hint {
  font-size: 9px;
  color: var(--hub-fg-3);
  letter-spacing: 0.1em;
  text-transform: uppercase;
}
.branch-caret {
  color: var(--hub-fg-3);
  display: inline-flex;
  align-items: center;
  transition: transform 160ms var(--hub-ease);
}
.branch-caret.open {
  transform: rotate(180deg);
}
.branch-count {
  color: var(--hub-fg-3);
  font-size: 10px;
  margin-left: 2px;
}

.branch-popover {
  position: absolute;
  top: calc(100% + 6px);
  left: 0;
  min-width: 260px;
  max-width: 380px;
  max-height: 280px;
  overflow-y: auto;
  border: 1px solid var(--hub-line-strong);
  border-radius: 10px;
  background: var(--hub-bg-2);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
  z-index: 50;
  padding: 4px;
}
.branch-search {
  padding: 4px;
}
.branch-search input {
  width: 100%;
  padding: 6px 10px;
  border-radius: 6px;
  border: 1px solid var(--hub-line);
  background: var(--hub-bg-1);
  color: var(--hub-fg-0);
  font-family: var(--hub-font-mono);
  font-size: 11px;
  outline: none;
}
.branch-search input:focus {
  border-color: var(--hub-accent);
}

.branch-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.branch-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  padding: 6px 10px;
  border-radius: 6px;
  font-family: var(--hub-font-mono);
  font-size: 11px;
  color: var(--hub-fg-1);
  cursor: pointer;
  transition: background 140ms var(--hub-ease);
}
.branch-item:hover,
.branch-item:focus {
  background: var(--hub-bg-3);
  outline: none;
}
.branch-item.active {
  color: var(--hub-fg-0);
  background: color-mix(in oklab, var(--hub-accent) 14%, var(--hub-bg-2));
}
.branch-item-name {
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.branch-item-flag {
  font-size: 9px;
  color: var(--hub-fg-3);
  letter-spacing: 0.1em;
  text-transform: uppercase;
}
.branch-item-empty {
  padding: 10px;
  text-align: center;
  font-family: var(--hub-font-mono);
  font-size: 10px;
  color: var(--hub-fg-3);
}
.branch-loading {
  padding: 14px;
  text-align: center;
  font-family: var(--hub-font-mono);
  font-size: 10px;
  color: var(--hub-fg-3);
}
</style>
