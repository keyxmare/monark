<script setup lang="ts">
import { useI18n } from 'vue-i18n'

import { useLocale } from '@/shared/composables/useLocale'
import type { Locale } from '@/shared/composables/useLocale'

const { t } = useI18n()
const { availableLocales, currentLocale, setLocale } = useLocale()

const labels: Record<Locale, string> = {
  fr: 'FR',
  en: 'EN',
}

function toggle() {
  setLocale(currentLocale.value === 'fr' ? 'en' : 'fr')
}
</script>

<template>
  <button
    class="flex items-center gap-1 rounded-lg px-2 py-1.5 text-sm font-medium text-text transition-colors hover:bg-surface-muted"
    :aria-label="t('aria.changeLanguage')"
    data-testid="language-switcher"
    @click="toggle"
  >
    <span
      v-for="locale in availableLocales"
      :key="locale"
      :class="[
        'px-1 py-0.5 rounded text-xs font-semibold transition-colors',
        locale === currentLocale
          ? 'bg-primary text-white'
          : 'text-text-muted',
      ]"
      :data-testid="`language-option-${locale}`"
    >
      {{ labels[locale] }}
    </span>
  </button>
</template>
