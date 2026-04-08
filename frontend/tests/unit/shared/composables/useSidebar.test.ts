import { describe, expect, it } from 'vitest';

import { useSidebar } from '@/shared/composables/useSidebar';

describe('useSidebar', () => {
  it('starts with collapsed false', () => {
    const { collapsed } = useSidebar();
    expect(collapsed.value).toBe(false);
  });

  it('toggles collapsed state', () => {
    const { collapsed, toggle } = useSidebar();
    const initial = collapsed.value;
    toggle();
    expect(collapsed.value).toBe(!initial);
    toggle();
    expect(collapsed.value).toBe(initial);
  });

  it('starts with mobileOpen false', () => {
    const { mobileOpen } = useSidebar();
    expect(mobileOpen.value).toBe(false);
  });

  it('toggles mobile open state', () => {
    const { mobileOpen, toggleMobile } = useSidebar();
    toggleMobile();
    expect(mobileOpen.value).toBe(true);
    toggleMobile();
    expect(mobileOpen.value).toBe(false);
  });

  it('closes mobile sidebar', () => {
    const { mobileOpen, toggleMobile, closeMobile } = useSidebar();
    toggleMobile();
    expect(mobileOpen.value).toBe(true);
    closeMobile();
    expect(mobileOpen.value).toBe(false);
  });

  it('closeMobile is idempotent when already closed', () => {
    const { mobileOpen, closeMobile } = useSidebar();
    closeMobile();
    expect(mobileOpen.value).toBe(false);
  });

  it('shares state between calls', () => {
    const sidebar1 = useSidebar();
    const sidebar2 = useSidebar();
    sidebar1.toggle();
    expect(sidebar2.collapsed.value).toBe(sidebar1.collapsed.value);
  });
});
