import { computed, type WritableComputedRef } from 'vue';

import { useLocalStorage } from '@/shared/composables/useLocalStorage';
import { STORAGE_KEYS } from '@/shared/constants';
import { i18n } from '@/shared/i18n';

const AVAILABLE_LOCALES = ['fr', 'en'] as const;

export type Locale = (typeof AVAILABLE_LOCALES)[number];

const storedLocale = useLocalStorage<Locale>(STORAGE_KEYS.LOCALE, 'fr', { raw: true });
const localeRef = i18n.global.locale as unknown as WritableComputedRef<Locale>;

export function useLocale() {
  const currentLocale = computed<Locale>({
    get: () => localeRef.value,
    set: (value: Locale) => setLocale(value),
  });

  function setLocale(locale: Locale) {
    localeRef.value = locale;
    storedLocale.value = locale;
    document.documentElement.lang = locale;
  }

  return {
    availableLocales: AVAILABLE_LOCALES,
    currentLocale,
    setLocale,
  };
}
