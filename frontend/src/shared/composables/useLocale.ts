import { computed, type WritableComputedRef } from 'vue';

import { i18n } from '@/shared/i18n';

const STORAGE_KEY = 'monark_locale';
const AVAILABLE_LOCALES = ['fr', 'en'] as const;

export type Locale = (typeof AVAILABLE_LOCALES)[number];

const localeRef = i18n.global.locale as unknown as WritableComputedRef<Locale>;

export function useLocale() {
  const currentLocale = computed<Locale>({
    get: () => localeRef.value,
    set: (value: Locale) => setLocale(value),
  });

  function setLocale(locale: Locale) {
    localeRef.value = locale;
    localStorage.setItem(STORAGE_KEY, locale);
    document.documentElement.lang = locale;
  }

  return {
    availableLocales: AVAILABLE_LOCALES,
    currentLocale,
    setLocale,
  };
}
