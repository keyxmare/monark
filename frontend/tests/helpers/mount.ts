import { mount } from '@vue/test-utils';
import type { ComponentMountingOptions } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { defineComponent } from 'vue';
import type { Component } from 'vue';

import { i18n } from '@/shared/i18n';

const RouterLinkStub = defineComponent({
  name: 'RouterLink',
  props: ['to'],
  template: '<a><slot /></a>',
});

const RouterViewStub = defineComponent({
  name: 'RouterView',
  template: '<div />',
});

export function mountWithPlugins<T extends Component>(
  component: T,
  options?: ComponentMountingOptions<T>,
) {
  const pinia = createPinia();
  setActivePinia(pinia);

  const globalOptions = options?.global ?? {};
  const defaultPlugins = [pinia, i18n];
  const defaultStubs = {
    RouterLink: RouterLinkStub,
    RouterView: RouterViewStub,
  };

  return mount(component as any, {
    ...options,
    global: {
      ...globalOptions,
      plugins: [...defaultPlugins, ...(globalOptions.plugins ?? [])],
      stubs: {
        ...defaultStubs,
        ...((globalOptions.stubs as Record<string, unknown>) ?? {}),
      },
    },
  });
}
