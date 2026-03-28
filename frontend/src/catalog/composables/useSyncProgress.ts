import { useI18n } from 'vue-i18n';

import type { SyncJobProgress } from '@/catalog/services/provider.service';
import { useMercure } from '@/shared/composables/useMercure';
import { useToastStore } from '@/shared/stores/toast';

export function useSyncProgress() {
  const { t } = useI18n();
  const toastStore = useToastStore();

  function track(syncJobId: string, totalProjects: number): void {
    const toastId = toastStore.addToast({
      variant: 'progress',
      title: t('catalog.providers.syncAll'),
      message: t('catalog.providers.syncStarted', { count: totalProjects }),
      progress: { current: 0, total: totalProjects },
    });

    const { data, close } = useMercure<SyncJobProgress>(`/sync-jobs/${syncJobId}`, {
      onMessage(progress) {
        if (progress.status === 'completed') {
          toastStore.updateToast(toastId, {
            variant: 'success',
            title: t('catalog.providers.syncAll'),
            message: t('catalog.providers.syncStarted', { count: progress.totalProjects }),
            progress: { current: progress.completedProjects, total: progress.totalProjects },
            duration: 5000,
          });
          close();
        } else if (progress.status === 'failed') {
          toastStore.updateToast(toastId, {
            variant: 'error',
            title: t('catalog.providers.syncAll'),
            message: t('common.errors.failedToSync'),
            duration: 8000,
          });
          close();
        } else {
          toastStore.updateToast(toastId, {
            progress: { current: progress.completedProjects, total: progress.totalProjects },
          });
        }
      },
    });

    const originalRemove = toastStore.removeToast.bind(toastStore);
    const unwatch = toastStore.$onAction(({ name, args }) => {
      if (name === 'removeToast' && args[0] === toastId) {
        close();
        unwatch();
      }
    });
  }

  return { track };
}
