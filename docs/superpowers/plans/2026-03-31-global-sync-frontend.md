# Plan: Global Sync Workflow (Frontend)

**Date**: 2026-03-31
**Goal**: Unify all sync buttons into a single global workflow with persistent breadcrumb progress banner, replacing individual toast-based sync flows.
**Architecture**: Vue 3 Composition API, Pinia, vue-i18n, Vitest, Mercure SSE
**Runtime**: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend pnpm ...`

---

## Task 1 — Types

**Files**
- CREATE `frontend/src/shared/types/globalSync.ts`

### Steps

- [ ] Create `globalSync.ts`

```ts
export type SyncStepName = 'sync_projects' | 'sync_versions' | 'scan_cve';
export type SyncStatus = 'running' | 'completed' | 'failed';

export interface GlobalSyncState {
  syncId: string;
  status: SyncStatus;
  currentStep: 1 | 2 | 3;
  currentStepName: SyncStepName;
  stepProgress: number;
  stepTotal: number;
  completedSteps: SyncStepName[];
  message?: string;
}

export const STEP_LABELS: Record<SyncStepName, string> = {
  sync_projects: 'Sync Projets',
  sync_versions: 'Sync Versions',
  scan_cve: 'Scan CVE',
};

export const STEP_ORDER: SyncStepName[] = ['sync_projects', 'sync_versions', 'scan_cve'];
```

---

## Task 2 — Service

**Files**
- CREATE `frontend/src/shared/services/sync.service.ts`

### Steps

- [ ] Create `sync.service.ts`

```ts
import type { GlobalSyncState } from '@/shared/types/globalSync';

const BASE = '/api/v1';

interface StartSyncResponse {
  syncId: string;
  status: string;
  currentStep: number;
}

interface CurrentSyncResponse {
  data: GlobalSyncState | null;
}

export const syncService = {
  async startSync(): Promise<StartSyncResponse> {
    const res = await fetch(`${BASE}/sync`, { method: 'POST' });
    if (res.status === 409) throw new Error('sync_already_running');
    if (!res.ok) throw new Error('sync_start_failed');
    return res.json();
  },

  async getCurrentSync(): Promise<GlobalSyncState | null> {
    const res = await fetch(`${BASE}/sync/current`);
    if (!res.ok) return null;
    const body: CurrentSyncResponse = await res.json();
    return body.data;
  },
};
```

---

## Task 3 — Composable `useGlobalSync`

**Files**
- CREATE `frontend/src/shared/composables/useGlobalSync.ts`

### Steps

- [ ] Create `useGlobalSync.ts`

```ts
import { computed, inject, provide, ref, watch, type InjectionKey, type Ref } from 'vue';

import { useMercure } from '@/shared/composables/useMercure';
import { syncService } from '@/shared/services/sync.service';
import type { GlobalSyncState } from '@/shared/types/globalSync';

export interface UseGlobalSyncReturn {
  currentSync: Ref<GlobalSyncState | null>;
  isRunning: Ref<boolean>;
  startSync: () => Promise<void>;
  loadCurrent: () => Promise<void>;
  onStepCompleted: (cb: (stepName: string) => void) => void;
}

export const GLOBAL_SYNC_KEY: InjectionKey<UseGlobalSyncReturn> = Symbol('globalSync');

export function createGlobalSync(): UseGlobalSyncReturn {
  const currentSync = ref<GlobalSyncState | null>(null);
  const isRunning = computed(() => currentSync.value?.status === 'running');
  const stepCompletedCallbacks: Array<(stepName: string) => void> = [];

  let closeMercure: (() => void) | null = null;
  let completedTimeout: ReturnType<typeof setTimeout> | null = null;

  function subscribeToMercure(syncId: string) {
    if (closeMercure) closeMercure();
    const { close } = useMercure<GlobalSyncState>(`/global-sync/${syncId}`, {
      onMessage(update) {
        const prevCompleted = currentSync.value?.completedSteps ?? [];
        currentSync.value = update;

        const newCompleted = update.completedSteps.filter((s) => !prevCompleted.includes(s));
        for (const step of newCompleted) {
          for (const cb of stepCompletedCallbacks) cb(step);
        }

        if (update.status === 'completed') {
          close();
          closeMercure = null;
          completedTimeout = setTimeout(() => {
            currentSync.value = null;
          }, 3000);
        }
      },
    });
    closeMercure = close;
  }

  async function loadCurrent() {
    const state = await syncService.getCurrentSync();
    if (state) {
      currentSync.value = state;
      subscribeToMercure(state.syncId);
    }
  }

  async function startSync() {
    const result = await syncService.startSync();
    const state = await syncService.getCurrentSync();
    if (state) {
      currentSync.value = state;
      subscribeToMercure(result.syncId);
    }
  }

  function onStepCompleted(cb: (stepName: string) => void) {
    stepCompletedCallbacks.push(cb);
  }

  return { currentSync: currentSync as Ref<GlobalSyncState | null>, isRunning: isRunning as Ref<boolean>, startSync, loadCurrent, onStepCompleted };
}

export function provideGlobalSync(): UseGlobalSyncReturn {
  const instance = createGlobalSync();
  provide(GLOBAL_SYNC_KEY, instance);
  return instance;
}

export function useGlobalSync(): UseGlobalSyncReturn {
  const instance = inject(GLOBAL_SYNC_KEY);
  if (!instance) throw new Error('useGlobalSync: missing provideGlobalSync() in parent');
  return instance;
}
```

---

## Task 4 — Component `SyncProgressBanner`

**Files**
- CREATE `frontend/src/shared/components/SyncProgressBanner.vue`

### Steps

- [ ] Create `SyncProgressBanner.vue`

```vue
<script setup lang="ts">
import { computed, ref, watch } from 'vue';

import { useGlobalSync } from '@/shared/composables/useGlobalSync';
import { STEP_ORDER, type SyncStepName } from '@/shared/types/globalSync';

const { currentSync } = useGlobalSync();

const visible = ref(false);
const fadingOut = ref(false);

watch(
  currentSync,
  (val) => {
    if (val) {
      fadingOut.value = false;
      visible.value = true;
    } else {
      fadingOut.value = true;
      setTimeout(() => {
        visible.value = false;
        fadingOut.value = false;
      }, 500);
    }
  },
  { immediate: true },
);

const progressPercent = computed(() => {
  if (!currentSync.value) return 0;
  const { stepProgress, stepTotal } = currentSync.value;
  if (!stepTotal) return 0;
  return Math.round((stepProgress / stepTotal) * 100);
});

function stepState(stepName: SyncStepName): 'completed' | 'active' | 'pending' {
  if (!currentSync.value) return 'pending';
  const { completedSteps, currentStepName, status } = currentSync.value;
  if (completedSteps.includes(stepName)) return 'completed';
  if (status === 'completed') return 'completed';
  if (currentStepName === stepName) return 'active';
  return 'pending';
}

const STEP_LABELS: Record<SyncStepName, string> = {
  sync_projects: 'Sync Projets',
  sync_versions: 'Sync Versions',
  scan_cve: 'Scan CVE',
};
</script>

<template>
  <Transition name="banner">
    <div
      v-if="visible"
      :class="[
        'border-b border-primary/20 bg-primary/5 px-6 py-3',
        fadingOut ? 'opacity-0' : 'opacity-100',
        'transition-opacity duration-500',
      ]"
      data-testid="sync-progress-banner"
      role="status"
      aria-live="polite"
    >
      <div class="flex flex-col gap-2">
        <div class="flex items-center gap-4">
          <div
            v-for="(stepName, index) in STEP_ORDER"
            :key="stepName"
            class="flex items-center gap-2"
          >
            <div class="flex items-center gap-1.5">
              <span
                v-if="stepState(stepName) === 'completed'"
                class="flex h-5 w-5 items-center justify-center rounded-full bg-success text-white text-xs font-bold"
                data-testid="step-completed"
              >
                ✓
              </span>
              <span
                v-else-if="stepState(stepName) === 'active'"
                class="flex h-5 w-5 items-center justify-center rounded-full bg-primary text-white text-xs"
                data-testid="step-active"
              >
                ●
              </span>
              <span
                v-else
                class="flex h-5 w-5 items-center justify-center rounded-full border-2 border-border text-text-muted text-xs"
                data-testid="step-pending"
              >
                ○
              </span>
              <span
                :class="{
                  'font-medium text-success': stepState(stepName) === 'completed',
                  'font-medium text-primary': stepState(stepName) === 'active',
                  'text-text-muted': stepState(stepName) === 'pending',
                }"
                class="text-sm"
              >
                {{ STEP_LABELS[stepName] }}
                <span
                  v-if="stepState(stepName) === 'active' && currentSync?.stepTotal"
                  class="text-xs font-normal"
                >
                  ({{ currentSync.stepProgress }}/{{ currentSync.stepTotal }})
                </span>
              </span>
            </div>
            <span v-if="index < STEP_ORDER.length - 1" class="text-border">────</span>
          </div>
        </div>

        <div v-if="currentSync?.status === 'running'" class="flex items-center gap-3">
          <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-border">
            <div
              class="h-full rounded-full bg-primary transition-all duration-300"
              :style="{ width: `${progressPercent}%` }"
              data-testid="sync-progress-bar"
            />
          </div>
          <span v-if="currentSync.message" class="text-xs text-text-muted" data-testid="sync-message">
            {{ currentSync.message }}
          </span>
        </div>
      </div>
    </div>
  </Transition>
</template>
```

---

## Task 5 — Component `SyncButton`

**Files**
- CREATE `frontend/src/shared/components/SyncButton.vue`

### Steps

- [ ] Create `SyncButton.vue`

```vue
<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

import { useGlobalSync } from '@/shared/composables/useGlobalSync';

const { t } = useI18n();
const { isRunning, currentSync, startSync } = useGlobalSync();

const loading = ref(false);

async function handleClick() {
  if (isRunning.value || loading.value) return;
  loading.value = true;
  try {
    await startSync();
  } catch (err: unknown) {
    if (err instanceof Error && err.message !== 'sync_already_running') {
      console.error(err);
    }
  } finally {
    loading.value = false;
  }
}

function stepLabel(): string {
  if (!currentSync.value) return '';
  return `Step ${currentSync.value.currentStep}/3`;
}
</script>

<template>
  <button
    :disabled="isRunning || loading"
    class="flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-dark disabled:cursor-not-allowed disabled:opacity-60"
    data-testid="sync-button"
    @click="handleClick"
  >
    <svg
      v-if="isRunning || loading"
      class="h-4 w-4 animate-spin"
      fill="none"
      viewBox="0 0 24 24"
      aria-hidden="true"
    >
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
      <path
        class="opacity-75"
        fill="currentColor"
        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
      />
    </svg>
    <svg
      v-else
      class="h-4 w-4"
      fill="none"
      stroke="currentColor"
      stroke-width="1.5"
      viewBox="0 0 24 24"
      aria-hidden="true"
    >
      <path
        stroke-linecap="round"
        stroke-linejoin="round"
        d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"
      />
    </svg>
    <span>
      <template v-if="isRunning">Sync en cours — {{ stepLabel() }}</template>
      <template v-else>{{ t('sync.button.label', 'Synchroniser') }}</template>
    </span>
  </button>
</template>
```

---

## Task 6 — Layout Integration

**Files**
- EDIT `frontend/src/shared/layouts/DashboardLayout.vue`

### Steps

- [ ] Add `provideGlobalSync()` call and `<SyncProgressBanner />` to `DashboardLayout.vue`

```vue
<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';

import AppSidebar from '@/shared/components/AppSidebar.vue';
import AppTopbar from '@/shared/components/AppTopbar.vue';
import SyncProgressBanner from '@/shared/components/SyncProgressBanner.vue';
import { provideGlobalSync } from '@/shared/composables/useGlobalSync';
import { useSidebar } from '@/shared/composables/useSidebar';

const { t } = useI18n();
const { collapsed, mobileOpen } = useSidebar();
const { loadCurrent } = provideGlobalSync();

onMounted(loadCurrent);

const mainClasses = computed(() => [
  'min-h-screen transition-all duration-300',
  collapsed.value ? 'ml-16' : 'ml-64',
  'max-md:ml-0',
]);
</script>

<template>
  <div class="flex min-h-screen bg-surface-muted">
    <a
      href="#main-content"
      class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:rounded focus:bg-primary focus:px-4 focus:py-2 focus:text-white"
    >
      {{ t('aria.skipToContent') }}
    </a>
    <AppSidebar />

    <div
      v-if="mobileOpen"
      role="button"
      tabindex="0"
      :aria-label="t('aria.closeSidebar')"
      class="fixed inset-0 z-30 bg-black/50 md:hidden"
      data-testid="sidebar-overlay"
      @click="mobileOpen = false"
      @keydown.escape="mobileOpen = false"
    />

    <div :class="mainClasses" class="flex-1">
      <AppTopbar />
      <SyncProgressBanner />

      <main id="main-content" class="w-full p-6" data-testid="main-content">
        <slot />
      </main>
    </div>
  </div>
</template>
```

---

## Task 7 — Page Integration

**Files**
- EDIT `frontend/src/catalog/pages/ProjectList.vue` — add `<SyncButton />`
- EDIT `frontend/src/catalog/pages/LanguageList.vue` — add `<SyncButton />`
- EDIT `frontend/src/catalog/pages/FrameworkList.vue` — replace old sync button with `<SyncButton />`, remove `useSyncProgress`
- EDIT `frontend/src/dependency/pages/DependencyList.vue` — replace old sync button with `<SyncButton />`, remove `useDependencySyncProgress`
- EDIT `frontend/src/dependency/pages/VulnerabilityList.vue` — add `<SyncButton />`

### Steps

#### 7a — FrameworkList.vue

- [ ] Remove `useSyncProgress` import and usage
- [ ] Remove `syncing` ref and `handleSyncAll` function
- [ ] Add import for `SyncButton`
- [ ] Replace the old button with `<SyncButton />` in the header actions div

Diff (replace old button block):
```vue
<!-- REMOVE -->
<button
  :disabled="syncing"
  class="rounded-lg border border-primary bg-transparent px-4 py-2 text-sm font-medium text-primary transition-colors hover:bg-primary hover:text-white disabled:opacity-50"
  data-testid="framework-sync-all"
  @click="handleSyncAll"
>
  {{ syncing ? t('catalog.providers.syncing') : t('catalog.providers.syncAll') }}
</button>

<!-- ADD -->
<SyncButton />
```

- [ ] Add auto-refresh on step completion

```ts
import { useGlobalSync } from '@/shared/composables/useGlobalSync';
import SyncButton from '@/shared/components/SyncButton.vue';

const { onStepCompleted } = useGlobalSync();

onStepCompleted((step) => {
  if (step === 'sync_projects' || step === 'sync_versions') {
    frameworkStore.fetchAll(1, 1000, projectId);
  }
});
```

#### 7b — DependencyList.vue

- [ ] Remove `useDependencySyncProgress` import and usage
- [ ] Remove `syncing` ref and sync handler function
- [ ] Remove `toastStore` usage related to sync
- [ ] Add import for `SyncButton` and `useGlobalSync`
- [ ] Replace old sync button with `<SyncButton />`
- [ ] Add auto-refresh on step completion

```ts
import { useGlobalSync } from '@/shared/composables/useGlobalSync';
import SyncButton from '@/shared/components/SyncButton.vue';

const { onStepCompleted } = useGlobalSync();

onStepCompleted((step) => {
  if (step === 'sync_projects' || step === 'sync_versions') {
    dependencyStore.fetchAll(1, 1000);
  }
});
```

#### 7c — ProjectList.vue, LanguageList.vue, VulnerabilityList.vue

For each page, add to the header actions:

```ts
import { useGlobalSync } from '@/shared/composables/useGlobalSync';
import SyncButton from '@/shared/components/SyncButton.vue';

const { onStepCompleted } = useGlobalSync();
```

- ProjectList: refresh on `sync_projects` step
- LanguageList: refresh on `sync_projects` or `sync_versions`
- VulnerabilityList: refresh on `scan_cve`

Add `<SyncButton />` in the header `div` with other action buttons.

---

## Task 8 — Cleanup

**Files**
- DELETE `frontend/src/catalog/composables/useSyncProgress.ts`
- DELETE `frontend/src/dependency/composables/useDependencySyncProgress.ts`

### Steps

- [ ] Delete `useSyncProgress.ts` (replaced by `useGlobalSync`)
- [ ] Delete `useDependencySyncProgress.ts` (replaced by `useGlobalSync`)
- [ ] Verify no remaining imports of these files: `grep -r "useSyncProgress\|useDependencySyncProgress" frontend/src`
- [ ] Remove any lingering `SyncStatus` enum usage that was only used by the deleted composables if no longer referenced elsewhere

---

## Task 9 — Tests + Verification

**Files**
- CREATE `frontend/src/shared/composables/useGlobalSync.test.ts`
- CREATE `frontend/src/shared/components/SyncProgressBanner.test.ts`
- CREATE `frontend/src/shared/components/SyncButton.test.ts`

### Steps

- [ ] Write composable tests

```ts
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { createGlobalSync } from '@/shared/composables/useGlobalSync';
import { syncService } from '@/shared/services/sync.service';

vi.mock('@/shared/services/sync.service');
vi.mock('@/shared/composables/useMercure', () => ({
  useMercure: vi.fn(() => ({ data: { value: null }, connected: { value: false }, exhausted: { value: false }, close: vi.fn() })),
}));

describe('createGlobalSync', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('loadCurrent sets currentSync when a running sync exists', async () => {
    const mockState = {
      syncId: 'abc',
      status: 'running' as const,
      currentStep: 1 as const,
      currentStepName: 'sync_projects' as const,
      stepProgress: 0,
      stepTotal: 5,
      completedSteps: [],
    };
    vi.mocked(syncService.getCurrentSync).mockResolvedValue(mockState);

    const { currentSync, loadCurrent } = createGlobalSync();
    await loadCurrent();

    expect(currentSync.value).toEqual(mockState);
  });

  it('loadCurrent leaves currentSync null when no sync running', async () => {
    vi.mocked(syncService.getCurrentSync).mockResolvedValue(null);

    const { currentSync, loadCurrent } = createGlobalSync();
    await loadCurrent();

    expect(currentSync.value).toBeNull();
  });

  it('isRunning is true when status is running', async () => {
    vi.mocked(syncService.getCurrentSync).mockResolvedValue({
      syncId: 'abc',
      status: 'running',
      currentStep: 2,
      currentStepName: 'sync_versions',
      stepProgress: 10,
      stepTotal: 100,
      completedSteps: ['sync_projects'],
    });

    const { isRunning, loadCurrent } = createGlobalSync();
    await loadCurrent();
    expect(isRunning.value).toBe(true);
  });

  it('startSync calls syncService.startSync and subscribes to mercure', async () => {
    vi.mocked(syncService.startSync).mockResolvedValue({ syncId: 'xyz', status: 'running', currentStep: 1 });
    vi.mocked(syncService.getCurrentSync).mockResolvedValue({
      syncId: 'xyz',
      status: 'running',
      currentStep: 1,
      currentStepName: 'sync_projects',
      stepProgress: 0,
      stepTotal: 3,
      completedSteps: [],
    });

    const { startSync, currentSync } = createGlobalSync();
    await startSync();

    expect(syncService.startSync).toHaveBeenCalledOnce();
    expect(currentSync.value?.syncId).toBe('xyz');
  });

  it('onStepCompleted callback fires when a new step appears in completedSteps', async () => {
    const { useMercure } = await import('@/shared/composables/useMercure');
    let messageHandler: ((data: unknown) => void) | undefined;
    vi.mocked(useMercure).mockImplementation((_topic, opts) => {
      messageHandler = opts?.onMessage as (data: unknown) => void;
      return { data: { value: null }, connected: { value: false }, exhausted: { value: false }, close: vi.fn() };
    });

    vi.mocked(syncService.getCurrentSync).mockResolvedValue({
      syncId: 'abc',
      status: 'running',
      currentStep: 1,
      currentStepName: 'sync_projects',
      stepProgress: 0,
      stepTotal: 2,
      completedSteps: [],
    });

    const { loadCurrent, onStepCompleted } = createGlobalSync();
    await loadCurrent();

    const completedSteps: string[] = [];
    onStepCompleted((step) => completedSteps.push(step));

    messageHandler?.({
      syncId: 'abc',
      status: 'running',
      currentStep: 2,
      currentStepName: 'sync_versions',
      stepProgress: 0,
      stepTotal: 10,
      completedSteps: ['sync_projects'],
    });

    expect(completedSteps).toEqual(['sync_projects']);
  });
});
```

- [ ] Write SyncProgressBanner tests

```ts
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import SyncProgressBanner from '@/shared/components/SyncProgressBanner.vue';
import { GLOBAL_SYNC_KEY } from '@/shared/composables/useGlobalSync';
import type { GlobalSyncState } from '@/shared/types/globalSync';
import { ref } from 'vue';

function mountWithSync(state: GlobalSyncState | null) {
  const currentSync = ref(state);
  const isRunning = ref(state?.status === 'running');
  return mount(SyncProgressBanner, {
    global: {
      provide: {
        [GLOBAL_SYNC_KEY as symbol]: { currentSync, isRunning, startSync: vi.fn(), loadCurrent: vi.fn(), onStepCompleted: vi.fn() },
      },
    },
  });
}

describe('SyncProgressBanner', () => {
  it('is hidden when no sync', async () => {
    const wrapper = mountWithSync(null);
    await wrapper.vm.$nextTick();
    expect(wrapper.find('[data-testid="sync-progress-banner"]').exists()).toBe(false);
  });

  it('shows all 3 steps when running', async () => {
    const wrapper = mountWithSync({
      syncId: 'abc',
      status: 'running',
      currentStep: 1,
      currentStepName: 'sync_projects',
      stepProgress: 2,
      stepTotal: 5,
      completedSteps: [],
    });
    await wrapper.vm.$nextTick();
    expect(wrapper.find('[data-testid="sync-progress-banner"]').exists()).toBe(true);
    expect(wrapper.findAll('[data-testid="step-active"]')).toHaveLength(1);
    expect(wrapper.findAll('[data-testid="step-pending"]')).toHaveLength(2);
  });

  it('shows progress bar with correct width', async () => {
    const wrapper = mountWithSync({
      syncId: 'abc',
      status: 'running',
      currentStep: 2,
      currentStepName: 'sync_versions',
      stepProgress: 50,
      stepTotal: 100,
      completedSteps: ['sync_projects'],
    });
    await wrapper.vm.$nextTick();
    const bar = wrapper.find('[data-testid="sync-progress-bar"]');
    expect(bar.attributes('style')).toContain('50%');
  });

  it('shows all steps as completed when status is completed', async () => {
    const wrapper = mountWithSync({
      syncId: 'abc',
      status: 'completed',
      currentStep: 3,
      currentStepName: 'scan_cve',
      stepProgress: 0,
      stepTotal: 0,
      completedSteps: ['sync_projects', 'sync_versions', 'scan_cve'],
    });
    await wrapper.vm.$nextTick();
    expect(wrapper.findAll('[data-testid="step-completed"]')).toHaveLength(3);
  });
});
```

- [ ] Write SyncButton tests

```ts
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';

import SyncButton from '@/shared/components/SyncButton.vue';
import { GLOBAL_SYNC_KEY } from '@/shared/composables/useGlobalSync';

function mountButton(isRunning: boolean, startSync = vi.fn()) {
  const currentSync = ref(isRunning ? {
    syncId: 'x', status: 'running', currentStep: 1, currentStepName: 'sync_projects',
    stepProgress: 0, stepTotal: 0, completedSteps: [],
  } : null);
  return mount(SyncButton, {
    global: {
      provide: {
        [GLOBAL_SYNC_KEY as symbol]: {
          currentSync,
          isRunning: ref(isRunning),
          startSync,
          loadCurrent: vi.fn(),
          onStepCompleted: vi.fn(),
        },
      },
      stubs: { 'i18n-t': true },
    },
  });
}

describe('SyncButton', () => {
  it('is enabled when not running', () => {
    const wrapper = mountButton(false);
    expect(wrapper.find('[data-testid="sync-button"]').attributes('disabled')).toBeUndefined();
  });

  it('is disabled when running', () => {
    const wrapper = mountButton(true);
    expect(wrapper.find('[data-testid="sync-button"]').attributes('disabled')).toBeDefined();
  });

  it('calls startSync on click', async () => {
    const startSync = vi.fn().mockResolvedValue(undefined);
    const wrapper = mountButton(false, startSync);
    await wrapper.find('[data-testid="sync-button"]').trigger('click');
    expect(startSync).toHaveBeenCalledOnce();
  });

  it('shows step info when running', () => {
    const wrapper = mountButton(true);
    expect(wrapper.text()).toContain('Step 1/3');
  });
});
```

- [ ] Run lint + format

```bash
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend pnpm lint
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend pnpm format
```

- [ ] Run tests

```bash
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend pnpm test --run
```

- [ ] Verify no orphan imports

```bash
grep -r "useSyncProgress\|useDependencySyncProgress" frontend/src
```

- [ ] Verify type-check passes

```bash
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend pnpm type-check
```
