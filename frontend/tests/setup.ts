import { beforeEach } from 'vitest'
import type { WritableComputedRef } from 'vue'

import { i18n } from '@/shared/i18n'

const localeRef = i18n.global.locale as unknown as WritableComputedRef<string>

beforeEach(() => {
  localeRef.value = 'en'
})
