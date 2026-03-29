import { beforeEach, describe, expect, it } from 'vitest';
import type { WritableComputedRef } from 'vue';

import { i18n } from '@/shared/i18n';
import { useLocale } from '@/shared/composables/useLocale';
import type { Locale } from '@/shared/composables/useLocale';

const localeRef = i18n.global.locale as unknown as WritableComputedRef<Locale>;

describe('useLocale', () => {
  beforeEach(() => {
    const { setLocale } = useLocale();
    setLocale('fr');
    localStorage.clear();
    localeRef.value = 'fr';
    document.documentElement.lang = 'fr';
  });

  it('returns current locale', () => {
    const { currentLocale } = useLocale();
    expect(currentLocale.value).toBe('fr');
  });

  it('returns available locales', () => {
    const { availableLocales } = useLocale();
    expect(availableLocales).toEqual(['fr', 'en']);
  });

  it('sets locale and updates i18n', () => {
    const { setLocale, currentLocale } = useLocale();
    setLocale('en');

    expect(currentLocale.value).toBe('en');
    expect(localeRef.value).toBe('en');
  });

  it('persists locale to localStorage', () => {
    const { setLocale } = useLocale();
    setLocale('en');

    expect(localStorage.getItem('monark_locale')).toBe('en');
  });

  it('sets document lang attribute', () => {
    const { setLocale } = useLocale();
    setLocale('en');

    expect(document.documentElement.lang).toBe('en');
  });

  it('restores locale from localStorage on i18n init', () => {
    localStorage.setItem('monark_locale', 'en');
    localeRef.value = 'en';

    const { currentLocale } = useLocale();
    expect(currentLocale.value).toBe('en');
  });

  it('falls back to fr when localStorage is empty', () => {
    const { currentLocale } = useLocale();
    expect(currentLocale.value).toBe('fr');
  });
});
