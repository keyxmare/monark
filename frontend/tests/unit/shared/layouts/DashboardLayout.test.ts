import { mount } from '@vue/test-utils';
import { ref } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const collapsedRef = ref(false);
const mobileOpenRef = ref(false);

vi.mock('vue-i18n', () => ({
  useI18n: () => ({ t: (key: string) => key }),
}));

vi.mock('@/shared/composables/useSidebar', () => ({
  useSidebar: () => ({
    collapsed: collapsedRef,
    mobileOpen: mobileOpenRef,
  }),
}));

vi.mock('@/shared/components/AppSidebar.vue', () => ({
  default: { template: '<aside data-testid="app-sidebar-stub" />' },
}));

vi.mock('@/shared/components/AppTopbar.vue', () => ({
  default: { template: '<header data-testid="app-topbar-stub" />' },
}));

import DashboardLayout from '@/shared/layouts/DashboardLayout.vue';

function mountLayout(slotContent = '') {
  return mount(DashboardLayout, {
    slots: { default: slotContent || '<p>Page content</p>' },
  });
}

describe('DashboardLayout', () => {
  beforeEach(() => {
    collapsedRef.value = false;
    mobileOpenRef.value = false;
  });

  it('renders the sidebar', () => {
    const wrapper = mountLayout();
    expect(wrapper.find('[data-testid="app-sidebar-stub"]').exists()).toBe(true);
  });

  it('renders the topbar', () => {
    const wrapper = mountLayout();
    expect(wrapper.find('[data-testid="app-topbar-stub"]').exists()).toBe(true);
  });

  it('renders main-content area with slot content', () => {
    const wrapper = mountLayout('<p>Dashboard data</p>');
    const main = wrapper.find('[data-testid="main-content"]');
    expect(main.exists()).toBe(true);
    expect(main.text()).toContain('Dashboard data');
  });

  it('shows sidebar overlay when mobileOpen is true', () => {
    mobileOpenRef.value = true;
    const wrapper = mountLayout();
    expect(wrapper.find('[data-testid="sidebar-overlay"]').exists()).toBe(true);
  });

  it('hides sidebar overlay when mobileOpen is false', () => {
    mobileOpenRef.value = false;
    const wrapper = mountLayout();
    expect(wrapper.find('[data-testid="sidebar-overlay"]').exists()).toBe(false);
  });

  it('closes mobile sidebar on overlay click', async () => {
    mobileOpenRef.value = true;
    const wrapper = mountLayout();
    await wrapper.find('[data-testid="sidebar-overlay"]').trigger('click');
    expect(mobileOpenRef.value).toBe(false);
  });

  it('applies ml-64 class when sidebar is expanded', () => {
    collapsedRef.value = false;
    const wrapper = mountLayout();
    const mainDiv = wrapper.find('[data-testid="main-content"]').element.parentElement!;
    expect(mainDiv.className).toContain('ml-64');
  });

  it('applies ml-16 class when sidebar is collapsed', () => {
    collapsedRef.value = true;
    const wrapper = mountLayout();
    const mainDiv = wrapper.find('[data-testid="main-content"]').element.parentElement!;
    expect(mainDiv.className).toContain('ml-16');
  });
});
