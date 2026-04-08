import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import ProviderIcon from '@/catalog/components/ProviderIcon.vue';

describe('ProviderIcon', () => {
  it('renders gitlab SVG when type is gitlab', () => {
    const wrapper = mount(ProviderIcon, { props: { type: 'gitlab' } });
    expect(wrapper.find('[data-testid="provider-icon-gitlab"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="provider-icon-github"]').exists()).toBe(false);
    expect(wrapper.find('[data-testid="provider-icon-bitbucket"]').exists()).toBe(false);
  });

  it('renders github SVG when type is github', () => {
    const wrapper = mount(ProviderIcon, { props: { type: 'github' } });
    expect(wrapper.find('[data-testid="provider-icon-github"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="provider-icon-gitlab"]').exists()).toBe(false);
  });

  it('renders bitbucket SVG when type is bitbucket', () => {
    const wrapper = mount(ProviderIcon, { props: { type: 'bitbucket' } });
    expect(wrapper.find('[data-testid="provider-icon-bitbucket"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="provider-icon-github"]').exists()).toBe(false);
  });

  it('uses default size of 20', () => {
    const wrapper = mount(ProviderIcon, { props: { type: 'github' } });
    const svg = wrapper.find('svg');
    expect(svg.attributes('width')).toBe('20');
    expect(svg.attributes('height')).toBe('20');
  });

  it('accepts custom size prop', () => {
    const wrapper = mount(ProviderIcon, { props: { type: 'github', size: 32 } });
    const svg = wrapper.find('svg');
    expect(svg.attributes('width')).toBe('32');
    expect(svg.attributes('height')).toBe('32');
  });
});
