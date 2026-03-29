import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import DropdownMenu from '@/shared/components/DropdownMenu.vue';
import type { DropdownMenuItem } from '@/shared/components/DropdownMenu.vue';

const items: DropdownMenuItem[] = [
  { action: 'edit', label: 'Edit' },
  { action: 'delete', label: 'Delete', variant: 'danger' },
  { action: 'archive', label: 'Archive', disabled: true },
];

function mountMenu(overrides: DropdownMenuItem[] = items) {
  return mount(DropdownMenu, {
    props: { items: overrides },
    attachTo: document.body,
  });
}

describe('DropdownMenu', () => {
  it('renders the trigger button', () => {
    const wrapper = mountMenu();
    expect(wrapper.find('[data-testid="dropdown-trigger"]').exists()).toBe(true);
  });

  it('does not show panel initially', () => {
    const wrapper = mountMenu();
    expect(wrapper.find('[data-testid="dropdown-panel"]').exists()).toBe(false);
  });

  it('shows panel after trigger click', async () => {
    const wrapper = mountMenu();
    await wrapper.find('[data-testid="dropdown-trigger"]').trigger('click');
    expect(wrapper.find('[data-testid="dropdown-panel"]').exists()).toBe(true);
  });

  it('renders all items', async () => {
    const wrapper = mountMenu();
    await wrapper.find('[data-testid="dropdown-trigger"]').trigger('click');
    expect(wrapper.findAll('[role="menuitem"]').length).toBe(3);
  });

  it('emits select with action on item click', async () => {
    const wrapper = mountMenu();
    await wrapper.find('[data-testid="dropdown-trigger"]').trigger('click');
    await wrapper.find('[data-testid="dropdown-item-edit"]').trigger('click');
    expect(wrapper.emitted('select')?.[0]).toEqual(['edit']);
  });

  it('does not emit select for disabled item', async () => {
    const wrapper = mountMenu();
    await wrapper.find('[data-testid="dropdown-trigger"]').trigger('click');
    await wrapper.find('[data-testid="dropdown-item-archive"]').trigger('click');
    expect(wrapper.emitted('select')).toBeUndefined();
  });

  it('closes on Escape keydown', async () => {
    const wrapper = mountMenu();
    await wrapper.find('[data-testid="dropdown-trigger"]').trigger('click');
    expect(wrapper.find('[data-testid="dropdown-panel"]').exists()).toBe(true);
    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
    await wrapper.vm.$nextTick();
    expect(wrapper.find('[data-testid="dropdown-panel"]').exists()).toBe(false);
  });

  it('sets aria-expanded on trigger', async () => {
    const wrapper = mountMenu();
    const trigger = wrapper.find('[data-testid="dropdown-trigger"]');
    expect(trigger.attributes('aria-expanded')).toBe('false');
    await trigger.trigger('click');
    expect(trigger.attributes('aria-expanded')).toBe('true');
  });

  it('closes panel after selecting an item', async () => {
    const wrapper = mountMenu();
    await wrapper.find('[data-testid="dropdown-trigger"]').trigger('click');
    await wrapper.find('[data-testid="dropdown-item-edit"]').trigger('click');
    expect(wrapper.find('[data-testid="dropdown-panel"]').exists()).toBe(false);
  });
});
