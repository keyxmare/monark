import { computed, ref, type Ref } from 'vue';

import { useMercure } from '@/shared/composables/useMercure';
import { syncService } from '@/shared/services/sync.service';
import type { GlobalSyncState } from '@/shared/types/globalSync';

export interface UseGlobalSyncReturn {
  currentSync: Ref<GlobalSyncState | null>;
  isRunning: Ref<boolean>;
  startSync: (projectId?: string) => Promise<void>;
  loadCurrent: () => Promise<void>;
  onStepCompleted: (cb: (stepName: string) => void) => void;
}

const currentSync = ref<GlobalSyncState | null>(null);
const isRunning = computed(() => currentSync.value?.status === 'running');
const stepCompletedCallbacks: Array<(stepName: string) => void> = [];

let closeMercure: (() => void) | null = null;

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
        setTimeout(() => {
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

async function startSync(projectId?: string) {
  const result = await syncService.startSync(projectId);
  const state = await syncService.getCurrentSync();
  if (state) {
    currentSync.value = state;
    subscribeToMercure(result.syncId);
  }
}

function onStepCompleted(cb: (stepName: string) => void) {
  stepCompletedCallbacks.push(cb);
}

const singleton: UseGlobalSyncReturn = {
  currentSync: currentSync as Ref<GlobalSyncState | null>,
  isRunning: isRunning as Ref<boolean>,
  startSync,
  loadCurrent,
  onStepCompleted,
};

export function provideGlobalSync(): UseGlobalSyncReturn {
  return singleton;
}

export function useGlobalSync(): UseGlobalSyncReturn {
  return singleton;
}
