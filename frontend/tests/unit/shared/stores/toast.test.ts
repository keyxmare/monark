import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';

import { useToastStore } from '@/shared/stores/toast';

describe('Toast Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('adds a toast and returns an id', () => {
    const store = useToastStore();
    const id = store.addToast({ variant: 'success', title: 'Done' });

    expect(id).toBeTruthy();
    expect(store.toasts).toHaveLength(1);
    expect(store.toasts[0].title).toBe('Done');
    expect(store.toasts[0].variant).toBe('success');
  });

  it('auto-removes non-progress toast after default duration', () => {
    const store = useToastStore();
    store.addToast({ variant: 'info', title: 'Info' });

    expect(store.toasts).toHaveLength(1);
    vi.advanceTimersByTime(5000);
    expect(store.toasts).toHaveLength(0);
  });

  it('does not auto-remove progress toast', () => {
    const store = useToastStore();
    store.addToast({ variant: 'progress', title: 'Loading' });

    vi.advanceTimersByTime(10000);
    expect(store.toasts).toHaveLength(1);
  });

  it('auto-removes with custom duration', () => {
    const store = useToastStore();
    store.addToast({ variant: 'success', title: 'Quick', duration: 2000 });

    vi.advanceTimersByTime(1999);
    expect(store.toasts).toHaveLength(1);
    vi.advanceTimersByTime(1);
    expect(store.toasts).toHaveLength(0);
  });

  it('does not auto-remove when duration is 0', () => {
    const store = useToastStore();
    store.addToast({ variant: 'error', title: 'Sticky', duration: 0 });

    vi.advanceTimersByTime(60000);
    expect(store.toasts).toHaveLength(1);
  });

  it('updates an existing toast', () => {
    const store = useToastStore();
    const id = store.addToast({ variant: 'progress', title: 'Loading' });

    store.updateToast(id, { title: 'Complete', variant: 'success' });

    expect(store.toasts[0].title).toBe('Complete');
    expect(store.toasts[0].variant).toBe('success');
  });

  it('schedules removal on update to non-progress variant', () => {
    const store = useToastStore();
    const id = store.addToast({ variant: 'progress', title: 'Loading' });

    store.updateToast(id, { variant: 'success', duration: 3000 });

    vi.advanceTimersByTime(3000);
    expect(store.toasts).toHaveLength(0);
  });

  it('ignores update for non-existent toast', () => {
    const store = useToastStore();
    store.updateToast('nonexistent', { title: 'Nope' });

    expect(store.toasts).toHaveLength(0);
  });

  it('removes a toast by id', () => {
    const store = useToastStore();
    const id = store.addToast({ variant: 'info', title: 'Remove me' });

    store.removeToast(id);
    expect(store.toasts).toHaveLength(0);
  });

  it('clears timer on manual removal', () => {
    const store = useToastStore();
    const id = store.addToast({ variant: 'success', title: 'Timer', duration: 5000 });

    store.removeToast(id);
    vi.advanceTimersByTime(5000);
    expect(store.toasts).toHaveLength(0);
  });

  it('updates progress on a toast', () => {
    const store = useToastStore();
    const id = store.addToast({
      variant: 'progress',
      title: 'Syncing',
      progress: { current: 0, total: 10 },
    });

    store.updateToast(id, { progress: { current: 5, total: 10 } });

    expect(store.toasts[0].progress?.current).toBe(5);
  });
});
