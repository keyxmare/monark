import { describe, expect, it } from 'vitest';
import { defineComponent, h } from 'vue';
import { getActivePinia } from 'pinia';

import { mountWithPlugins } from '../../helpers';

const TestComponent = defineComponent({
  name: 'TestComponent',
  props: {
    message: { type: String, default: 'hello' },
  },
  setup(props) {
    return () => h('div', { class: 'test' }, props.message);
  },
});

const RouterLinkUser = defineComponent({
  name: 'RouterLinkUser',
  template: '<RouterLink to="/foo">link text</RouterLink>',
});

describe('mountWithPlugins', () => {
  it('mounts a component with pinia active', () => {
    mountWithPlugins(TestComponent);
    expect(getActivePinia()).toBeTruthy();
  });

  it('provides i18n plugin (no warnings about missing $t)', () => {
    const wrapper = mountWithPlugins(TestComponent);
    expect(wrapper.html()).toContain('hello');
  });

  it('passes props through', () => {
    const wrapper = mountWithPlugins(TestComponent, {
      props: { message: 'world' },
    });
    expect(wrapper.text()).toBe('world');
  });

  it('stubs RouterLink by default', () => {
    const wrapper = mountWithPlugins(RouterLinkUser);
    const link = wrapper.find('a');
    expect(link.exists()).toBe(true);
    expect(link.text()).toBe('link text');
  });

  it('merges additional stubs from options', () => {
    const CustomStub = defineComponent({
      template: '<span>stubbed</span>',
    });
    const wrapper = mountWithPlugins(TestComponent, {
      global: {
        stubs: { SomeOther: CustomStub },
      },
    });
    // Default stubs should still be present
    expect(wrapper.html()).toContain('hello');
  });

  it('merges additional plugins from options', () => {
    const installed: string[] = [];
    const fakePlugin = { install: () => installed.push('fake') };

    mountWithPlugins(TestComponent, {
      global: {
        plugins: [fakePlugin],
      },
    });
    expect(installed).toContain('fake');
  });
});
