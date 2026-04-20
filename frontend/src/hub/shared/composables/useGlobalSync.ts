import { computed, ref, type Ref } from 'vue';

import { useMercure } from '@/hub/shared/composables/useMercure';
import { syncService } from '@/hub/shared/services/sync.service';
import type { GlobalSyncState } from '@/hub/shared/types/globalSync';

export interface UseGlobalSyncReturn {
  cancelSync: () => Promise<void>;
  currentSync: Ref<GlobalSyncState | null>;
  isRunning: Ref<boolean>;
  loadCurrent: () => Promise<void>;
  onStepCompleted: (cb: (stepName: string) => void) => void;
  startSync: (projectId?: string) => Promise<void>;
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

      if (update.status === 'completed' || update.status === 'failed') {
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

async function cancelSync() {
  await syncService.cancelSync();
  if (closeMercure) {
    closeMercure();
    closeMercure = null;
  }
  const state = await syncService.getCurrentSync();
  currentSync.value = state;
}

function onStepCompleted(cb: (stepName: string) => void) {
  stepCompletedCallbacks.push(cb);
}

const singleton: UseGlobalSyncReturn = {
  cancelSync,
  currentSync: currentSync as Ref<GlobalSyncState | null>,
  isRunning: isRunning as Ref<boolean>,
  loadCurrent,
  onStepCompleted,
  startSync,
};

export function provideGlobalSync(): UseGlobalSyncReturn {
  return singleton;
}

export function useGlobalSync(): UseGlobalSyncReturn {
  return singleton;
}
