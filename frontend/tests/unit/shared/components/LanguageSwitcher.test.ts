import { beforeEach, describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import type { WritableComputedRef } from 'vue'

import { i18n } from '@/shared/i18n'
import type { Locale } from '@/shared/composables/useLocale'
import LanguageSwitcher from '@/shared/components/LanguageSwitcher.vue'

const localeRef = i18n.global.locale as unknown as WritableComputedRef<Locale>

function mountSwitcher() {
  return mount(LanguageSwitcher, {
    global: {
      plugins: [i18n],
    },
  })
}

describe('LanguageSwitcher', () => {
  beforeEach(() => {
    localStorage.clear()
    localeRef.value = 'fr'
    document.documentElement.lang = 'fr'
  })

  it('renders with FR and EN options', () => {
    const wrapper = mountSwitcher()

    expect(wrapper.find('[data-testid="language-switcher"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="language-option-fr"]').text()).toBe('FR')
    expect(wrapper.find('[data-testid="language-option-en"]').text()).toBe('EN')
  })

  it('highlights active locale', () => {
    const wrapper = mountSwitcher()

    const frOption = wrapper.find('[data-testid="language-option-fr"]')
    expect(frOption.classes()).toContain('bg-primary')
  })

  it('toggles locale on click', async () => {
    const wrapper = mountSwitcher()

    await wrapper.find('[data-testid="language-switcher"]').trigger('click')

    expect(localeRef.value).toBe('en')
    expect(localStorage.getItem('monark_locale')).toBe('en')
  })

  it('toggles back to fr on second click', async () => {
    const wrapper = mountSwitcher()

    await wrapper.find('[data-testid="language-switcher"]').trigger('click')
    await wrapper.find('[data-testid="language-switcher"]').trigger('click')

    expect(localeRef.value).toBe('fr')
  })

  it('has an aria-label for accessibility', () => {
    const wrapper = mountSwitcher()

    const button = wrapper.find('[data-testid="language-switcher"]')
    expect(button.attributes('aria-label')).toBeTruthy()
  })
})
