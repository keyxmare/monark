import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import AppToast from '@/shared/components/AppToast.vue';
import type { Toast } from '@/shared/stores/toast';

function makeToast(overrides: Partial<Toast> = {}): Toast {
  return {
    id: 'toast-1',
    variant: 'success',
    title: 'Success!',
    ...overrides,
  };
}

function mountToast(toast: Toast) {
  return mount(AppToast, { props: { toast } });
}

describe('AppToast', () => {
  it('renders with role alert', () => {
    const wrapper = mountToast(makeToast());
    expect(wrapper.find('[role="alert"]').exists()).toBe(true);
  });

  it('displays the title', () => {
    const wrapper = mountToast(makeToast({ title: 'Done!' }));
    expect(wrapper.text()).toContain('Done!');
  });

  it('displays the message when provided', () => {
    const wrapper = mountToast(makeToast({ message: 'All good.' }));
    expect(wrapper.text()).toContain('All good.');
  });

  it('does not display message when not provided', () => {
    const wrapper = mountToast(makeToast({ message: undefined }));
    const paragraphs = wrapper.findAll('p');
    expect(paragraphs.length).toBe(1);
  });

  it('emits close with toast id on close click', async () => {
    const wrapper = mountToast(makeToast({ id: 'toast-42' }));
    await wrapper.find('[data-testid="toast-close"]').trigger('click');
    expect(wrapper.emitted('close')?.[0]).toEqual(['toast-42']);
  });

  it('applies success variant classes', () => {
    const wrapper = mountToast(makeToast({ variant: 'success' }));
    const el = wrapper.find('[role="alert"]');
    expect(el.classes()).toContain('border-green-500');
  });

  it('applies error variant classes', () => {
    const wrapper = mountToast(makeToast({ variant: 'error' }));
    const el = wrapper.find('[role="alert"]');
    expect(el.classes()).toContain('border-red-500');
  });

  it('applies info variant classes', () => {
    const wrapper = mountToast(makeToast({ variant: 'info' }));
    const el = wrapper.find('[role="alert"]');
    expect(el.classes()).toContain('border-blue-500');
  });

  it('shows progress bar for progress variant', () => {
    const wrapper = mountToast(makeToast({
      variant: 'progress',
      progress: { current: 3, total: 10 },
    }));
    expect(wrapper.text()).toContain('3/10');
    expect(wrapper.text()).toContain('30%');
  });

  it('shows 100% with green bar when complete', () => {
    const wrapper = mountToast(makeToast({
      variant: 'progress',
      progress: { current: 10, total: 10 },
    }));
    expect(wrapper.text()).toContain('100%');
    const bar = wrapper.find('.bg-green-500');
    expect(bar.exists()).toBe(true);
  });

  it('does not show progress bar for non-progress variant', () => {
    const wrapper = mountToast(makeToast({ variant: 'success' }));
    expect(wrapper.find('.bg-black\\/10').exists()).toBe(false);
  });
});
