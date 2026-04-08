import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import TechBadge from '@/shared/components/TechBadge.vue';

describe('TechBadge', () => {
  it('renders Angular red shield for version 17.x', () => {
    const wrapper = mount(TechBadge, {
      props: { name: 'Angular', version: '17.3.0' },
    });
    const img = wrapper.find('img');
    expect(img.exists()).toBe(true);
    expect(img.attributes('src')).toContain('angular/angular-original');
    expect(img.attributes('src')).not.toContain('angularjs');
  });

  it('renders AngularJS green logo for version 1.x', () => {
    const wrapper = mount(TechBadge, {
      props: { name: 'Angular', version: '1.8.3' },
    });
    const img = wrapper.find('img');
    expect(img.exists()).toBe(true);
    expect(img.attributes('src')).toContain('angularjs/angularjs-original');
  });

  it('defaults to Angular icon when no version provided', () => {
    const wrapper = mount(TechBadge, {
      props: { name: 'Angular' },
    });
    const img = wrapper.find('img');
    expect(img.exists()).toBe(true);
    expect(img.attributes('src')).toContain('angular/angular-original');
  });

  it('ignores version for non-Angular frameworks', () => {
    const wrapper = mount(TechBadge, {
      props: { name: 'Vue', version: '3.5.0' },
    });
    const img = wrapper.find('img');
    expect(img.attributes('src')).toContain('vuejs/vuejs-original');
  });

  it('renders text fallback for unknown framework', () => {
    const wrapper = mount(TechBadge, {
      props: { name: 'UnknownFramework' },
    });
    expect(wrapper.find('img').exists()).toBe(false);
    expect(wrapper.text()).toContain('UnknownFramework');
  });
});
