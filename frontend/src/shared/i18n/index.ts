import { createI18n } from 'vue-i18n';

import en from './locales/en.json';
import fr from './locales/fr.json';

export type MessageSchema = typeof en;

const datetimeFormats = {
  en: {
    short: { year: 'numeric', month: '2-digit', day: '2-digit' } as const,
    long: {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    } as const,
  },
  fr: {
    short: { year: 'numeric', month: '2-digit', day: '2-digit' } as const,
    long: {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    } as const,
  },
};

const STORAGE_KEY = 'monark_locale';
const savedLocale = localStorage.getItem(STORAGE_KEY);
const initialLocale = savedLocale === 'en' ? 'en' : 'fr';

export const i18n = createI18n<[MessageSchema], 'en' | 'fr'>({
  legacy: false,
  locale: initialLocale,
  fallbackLocale: 'en',
  globalInjection: true,
  messages: {
    en,
    fr,
  },
  datetimeFormats,
});
