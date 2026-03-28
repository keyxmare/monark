import { useI18n } from 'vue-i18n';

import { useMercure } from '@/shared/composables/useMercure';
import { useToastStore } from '@/shared/stores/toast';

interface DependencySyncProgress {
  syncId: string;
  completed: number;
  total: number;
  status: 'running' | 'completed';
  lastPackage: string;
}

const TIMEOUT_MS = 120000;

export function useDependencySyncProgress() {
  const { t } = useI18n();
  const toastStore = useToastStore();

  function track(syncId: string): void {
    const toastId = toastStore.addToast({
      variant: 'progress',
      title: t('dependency.dependencies.syncVersions'),
      message: t('dependency.dependencies.syncing'),
      progress: { current: 0, total: 1 },
    });

    let timeoutHandle: ReturnType<typeof setTimeout> | null = null;

    function resetTimeout() {
      if (timeoutHandle) clearTimeout(timeoutHandle);
      timeoutHandle = setTimeout(() => {
        toastStore.updateToast(toastId, {
          variant: 'error',
          title: t('dependency.dependencies.syncVersions'),
          message: t('common.errors.failedToSync'),
        });
        close();
      }, TIMEOUT_MS);
    }

    const { data, close } = useMercure<DependencySyncProgress>(`/dependency/sync/${syncId}`, {
      onMessage(progress) {
        resetTimeout();

        if (progress.status === 'completed') {
          if (timeoutHandle) clearTimeout(timeoutHandle);
          toastStore.updateToast(toastId, {
            variant: 'success',
            title: t('dependency.dependencies.syncVersions'),
            message: `${progress.completed}/${progress.total}`,
            progress: { current: progress.completed, total: progress.total },
          });
          close();
        } else {
          toastStore.updateToast(toastId, {
            message: progress.lastPackage,
            progress: { current: progress.completed, total: progress.total },
          });
        }
      },
    });

    resetTimeout();

    const unwatch = toastStore.$onAction(({ name, args }) => {
      if (name === 'removeToast' && args[0] === toastId) {
        if (timeoutHandle) clearTimeout(timeoutHandle);
        close();
        unwatch();
      }
    });
  }

  return { track };
}
