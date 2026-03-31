import { computed, inject, provide, ref, type InjectionKey, type Ref } from 'vue';

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

  return {
    currentSync: currentSync as Ref<GlobalSyncState | null>,
    isRunning: isRunning as Ref<boolean>,
    startSync,
    loadCurrent,
    onStepCompleted,
  };
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
