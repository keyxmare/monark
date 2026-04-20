import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import AuthLayout from '@/hub/shared/layouts/AuthLayout.vue';

const globalStubs = {
  stubs: {
    RouterLink: { template: '<a><slot /></a>' },
    AmbientBackground: true,
    MonarkMark: true,
  },
};

describe('AuthLayout', () => {
  it('renders the auth-container', () => {
    const wrapper = mount(AuthLayout, { global: globalStubs });
    expect(wrapper.find('[data-testid="auth-container"]').exists()).toBe(true);
  });

  it('renders slot content', () => {
    const wrapper = mount(AuthLayout, {
      slots: { default: '<p>Login form here</p>' },
      global: globalStubs,
    });

    expect(wrapper.text()).toContain('Login form here');
  });

  it('wraps slot inside auth-container', () => {
    const wrapper = mount(AuthLayout, {
      slots: { default: '<span class="inner">Content</span>' },
      global: globalStubs,
    });

    const container = wrapper.find('[data-testid="auth-container"]');
    expect(container.find('.inner').exists()).toBe(true);
  });
});
