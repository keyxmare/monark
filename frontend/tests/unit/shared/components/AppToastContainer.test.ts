import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const removeToastMock = vi.fn();
const toastsRef = { value: [] as Array<{ id: string; variant: string; title: string; message?: string }> };

vi.mock('@/shared/stores/toast', () => ({
  useToastStore: () => ({
    toasts: toastsRef.value,
    removeToast: removeToastMock,
  }),
}));

vi.mock('@/shared/components/AppToast.vue', () => ({
  default: {
    template: '<div :data-testid="`toast-${toast.id}`"><button data-testid="toast-close" @click="$emit(\'close\', toast.id)" /></div>',
    props: ['toast'],
    emits: ['close'],
  },
}));

import AppToastContainer from '@/shared/components/AppToastContainer.vue';

function mountContainer() {
  return mount(AppToastContainer, {
    global: {
      stubs: { Teleport: true, TransitionGroup: false },
    },
  });
}

describe('AppToastContainer', () => {
  beforeEach(() => {
    toastsRef.value = [];
    removeToastMock.mockClear();
  });

  it('renders the container', () => {
    const wrapper = mountContainer();
    expect(wrapper.find('[data-testid="toast-container"]').exists()).toBe(true);
  });

  it('renders no toasts when store is empty', () => {
    const wrapper = mountContainer();
    expect(wrapper.findAll('[data-testid="toast-close"]').length).toBe(0);
  });

  it('renders toasts from store', () => {
    toastsRef.value = [
      { id: 'a', variant: 'success', title: 'Ok' },
      { id: 'b', variant: 'error', title: 'Fail' },
    ];
    const wrapper = mountContainer();
    expect(wrapper.find('[data-testid="toast-a"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="toast-b"]').exists()).toBe(true);
  });

  it('calls removeToast on close event', async () => {
    toastsRef.value = [{ id: 'x', variant: 'info', title: 'Info' }];
    const wrapper = mountContainer();
    await wrapper.find('[data-testid="toast-close"]').trigger('click');
    expect(removeToastMock).toHaveBeenCalledWith('x');
  });
});
