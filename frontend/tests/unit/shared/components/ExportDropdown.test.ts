import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

vi.mock('vue-i18n', () => ({
  useI18n: () => ({ t: (key: string) => key }),
}));

import ExportDropdown from '@/shared/components/ExportDropdown.vue';

function mountExport() {
  return mount(ExportDropdown);
}

describe('ExportDropdown', () => {
  it('renders the trigger button', () => {
    const wrapper = mountExport();
    expect(wrapper.find('[data-testid="export-dropdown-trigger"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="export-dropdown-trigger"]').text()).toContain('common.actions.export');
  });

  it('does not show menu initially', () => {
    const wrapper = mountExport();
    expect(wrapper.find('[data-testid="export-dropdown-menu"]').exists()).toBe(false);
  });

  it('opens menu on trigger click', async () => {
    const wrapper = mountExport();
    await wrapper.find('[data-testid="export-dropdown-trigger"]').trigger('click');
    expect(wrapper.find('[data-testid="export-dropdown-menu"]').exists()).toBe(true);
  });

  it('shows CSV and PDF options', async () => {
    const wrapper = mountExport();
    await wrapper.find('[data-testid="export-dropdown-trigger"]').trigger('click');
    expect(wrapper.find('[data-testid="export-csv"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="export-pdf"]').exists()).toBe(true);
  });

  it('emits export with csv on CSV click', async () => {
    const wrapper = mountExport();
    await wrapper.find('[data-testid="export-dropdown-trigger"]').trigger('click');
    await wrapper.find('[data-testid="export-csv"]').trigger('mousedown');
    expect(wrapper.emitted('export')?.[0]).toEqual(['csv']);
  });

  it('emits export with pdf on PDF click', async () => {
    const wrapper = mountExport();
    await wrapper.find('[data-testid="export-dropdown-trigger"]').trigger('click');
    await wrapper.find('[data-testid="export-pdf"]').trigger('mousedown');
    expect(wrapper.emitted('export')?.[0]).toEqual(['pdf']);
  });

  it('closes menu after selection', async () => {
    const wrapper = mountExport();
    await wrapper.find('[data-testid="export-dropdown-trigger"]').trigger('click');
    await wrapper.find('[data-testid="export-csv"]').trigger('mousedown');
    expect(wrapper.find('[data-testid="export-dropdown-menu"]').exists()).toBe(false);
  });
});
