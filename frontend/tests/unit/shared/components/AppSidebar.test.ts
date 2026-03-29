import { ref } from 'vue';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('vue-router', () => ({
  RouterLink: { template: '<a><slot /></a>', props: ['to'] },
  useRoute: vi.fn(() => ({ params: {}, path: '/' })),
  useRouter: vi.fn(() => ({ push: vi.fn() })),
}));

vi.mock('vue-i18n', () => ({
  useI18n: () => ({ t: (key: string) => key }),
}));

const toggleMock = vi.fn();
const collapsedRef = ref(false);
const mobileOpenRef = ref(false);

vi.mock('@/shared/composables/useSidebar', async () => ({
  useSidebar: () => ({
    get collapsed() {
      return collapsedRef;
    },
    get mobileOpen() {
      return mobileOpenRef;
    },
    toggle: toggleMock,
  }),
}));

import AppSidebar from '@/shared/components/AppSidebar.vue';

function mountSidebar() {
  return mount(AppSidebar);
}

describe('AppSidebar', () => {
  beforeEach(() => {
    collapsedRef.value = false;
    mobileOpenRef.value = false;
    toggleMock.mockClear();
  });

  it('renders the sidebar', () => {
    const wrapper = mountSidebar();
    expect(wrapper.find('[data-testid="sidebar"]').exists()).toBe(true);
  });

  it('displays brand name when not collapsed', () => {
    const wrapper = mountSidebar();
    expect(wrapper.text()).toContain('Monark');
  });

  it('hides brand name when collapsed', () => {
    collapsedRef.value = true;
    const wrapper = mountSidebar();
    const brand = wrapper.findAll('span').filter((s) => s.text() === 'Monark');
    expect(brand.length).toBe(0);
  });

  it('renders navigation links', () => {
    const wrapper = mountSidebar();
    const links = wrapper.findAll('a');
    expect(links.length).toBeGreaterThanOrEqual(10);
  });

  it('renders section headings when not collapsed', () => {
    const wrapper = mountSidebar();
    expect(wrapper.text()).toContain('nav.sections.catalog');
    expect(wrapper.text()).toContain('nav.sections.dependency');
    expect(wrapper.text()).toContain('nav.sections.activity');
    expect(wrapper.text()).toContain('nav.sections.identity');
  });

  it('hides section headings when collapsed', () => {
    collapsedRef.value = true;
    const wrapper = mountSidebar();
    expect(wrapper.text()).not.toContain('nav.sections.catalog');
  });

  it('calls toggle when toggle button is clicked', async () => {
    const wrapper = mountSidebar();
    await wrapper.find('[data-testid="sidebar-toggle"]').trigger('click');
    expect(toggleMock).toHaveBeenCalledOnce();
  });

  it('has aria-label for accessibility', () => {
    const wrapper = mountSidebar();
    expect(wrapper.find('[data-testid="sidebar"]').attributes('aria-label')).toBe(
      'aria.mainNavigation',
    );
  });
});
