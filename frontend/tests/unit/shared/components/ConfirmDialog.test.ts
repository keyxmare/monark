import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

vi.mock('vue-i18n', () => ({
  useI18n: () => ({ t: (key: string) => key }),
}));

import ConfirmDialog from '@/shared/components/ConfirmDialog.vue';

function mountDialog(props: Partial<{ open: boolean; title: string; message: string; variant: 'danger' | 'default'; cancelLabel: string; confirmLabel: string }> = {}) {
  return mount(ConfirmDialog, {
    props: { open: true, ...props },
    global: {
      stubs: { Teleport: true },
    },
  });
}

describe('ConfirmDialog', () => {
  it('renders when open', () => {
    const wrapper = mountDialog();
    expect(wrapper.find('[data-testid="confirm-dialog"]').exists()).toBe(true);
  });

  it('shows default title from translation key', () => {
    const wrapper = mountDialog();
    expect(wrapper.find('[data-testid="confirm-dialog-title"]').text()).toBe('common.confirm.title');
  });

  it('shows custom title', () => {
    const wrapper = mountDialog({ title: 'Delete item?' });
    expect(wrapper.find('[data-testid="confirm-dialog-title"]').text()).toBe('Delete item?');
  });

  it('shows custom message', () => {
    const wrapper = mountDialog({ message: 'This cannot be undone.' });
    expect(wrapper.find('[data-testid="confirm-dialog-message"]').text()).toBe('This cannot be undone.');
  });

  it('shows default message from translation key', () => {
    const wrapper = mountDialog();
    expect(wrapper.find('[data-testid="confirm-dialog-message"]').text()).toBe('common.confirm.deleteMessage');
  });

  it('emits cancel on cancel click', async () => {
    const wrapper = mountDialog();
    await wrapper.find('[data-testid="confirm-dialog-cancel"]').trigger('click');
    expect(wrapper.emitted('cancel')).toHaveLength(1);
  });

  it('emits confirm on confirm click', async () => {
    const wrapper = mountDialog();
    await wrapper.find('[data-testid="confirm-dialog-confirm"]').trigger('click');
    expect(wrapper.emitted('confirm')).toHaveLength(1);
  });

  it('shows custom button labels', () => {
    const wrapper = mountDialog({ cancelLabel: 'No', confirmLabel: 'Yes' });
    expect(wrapper.find('[data-testid="confirm-dialog-cancel"]').text()).toBe('No');
    expect(wrapper.find('[data-testid="confirm-dialog-confirm"]').text()).toBe('Yes');
  });

  it('does not render form when closed', () => {
    const wrapper = mountDialog({ open: false });
    expect(wrapper.find('form').exists()).toBe(false);
  });

  it('applies danger variant class to confirm button', () => {
    const wrapper = mountDialog({ variant: 'danger' });
    const btn = wrapper.find('[data-testid="confirm-dialog-confirm"]');
    expect(btn.classes()).toContain('bg-danger');
  });

  it('has aria-labelledby and aria-describedby on dialog', () => {
    const wrapper = mountDialog();
    const dialog = wrapper.find('[data-testid="confirm-dialog"]');
    expect(dialog.attributes('aria-labelledby')).toBe('confirm-title');
    expect(dialog.attributes('aria-describedby')).toBe('confirm-message');
  });

  it('has id attributes on title and message elements', () => {
    const wrapper = mountDialog();
    expect(wrapper.find('#confirm-title').exists()).toBe(true);
    expect(wrapper.find('#confirm-message').exists()).toBe(true);
  });
});
