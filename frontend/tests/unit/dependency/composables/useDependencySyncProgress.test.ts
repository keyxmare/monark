import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';

interface DependencySyncProgress {
  syncId: string;
  completed: number;
  total: number;
  status: 'running' | 'completed';
  lastPackage: string;
}

let mercureOnMessage: ((data: DependencySyncProgress) => void) | undefined;
let mercureClose: ReturnType<typeof vi.fn>;

vi.mock('@/shared/composables/useMercure', () => ({
  useMercure: vi.fn(
    (_topic: string, options: { onMessage?: (data: DependencySyncProgress) => void } = {}) => {
      mercureOnMessage = options.onMessage;
      mercureClose = vi.fn();
      return {
        data: { value: null },
        connected: { value: false },
        close: mercureClose,
      };
    },
  ),
}));

vi.mock('vue-i18n', () => ({
  useI18n: () => ({
    t: (key: string) => key,
  }),
}));

import { useDependencySyncProgress } from '@/dependency/composables/useDependencySyncProgress';
import { useToastStore } from '@/shared/stores/toast';

describe('useDependencySyncProgress', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    mercureOnMessage = undefined;
  });

  it('creates a progress toast and subscribes to mercure', () => {
    const store = useToastStore();
    const { track } = useDependencySyncProgress();

    track('sync-1');

    expect(store.toasts).toHaveLength(1);
    expect(store.toasts[0].variant).toBe('progress');
    expect(mercureOnMessage).toBeDefined();
  });

  it('updates progress on running status', () => {
    const store = useToastStore();
    const { track } = useDependencySyncProgress();

    track('sync-1');

    mercureOnMessage?.({
      syncId: 'sync-1',
      completed: 3,
      total: 10,
      status: 'running',
      lastPackage: 'lodash',
    });

    expect(store.toasts[0].progress?.current).toBe(3);
    expect(store.toasts[0].message).toBe('lodash');
    expect(mercureClose).not.toHaveBeenCalled();
  });

  it('marks toast as success on completed and closes mercure', () => {
    const store = useToastStore();
    const { track } = useDependencySyncProgress();

    track('sync-1');

    mercureOnMessage?.({
      syncId: 'sync-1',
      completed: 10,
      total: 10,
      status: 'completed',
      lastPackage: 'vue',
    });

    expect(store.toasts[0].variant).toBe('success');
    expect(store.toasts[0].message).toBe('10/10');
    expect(mercureClose).toHaveBeenCalled();
  });
});
