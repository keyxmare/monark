import { ref } from 'vue';
import { defineStore } from 'pinia';
import type { Language } from '@/catalog/types/language';
import { languageService } from '@/catalog/services/language.service';
import { i18n } from '@/shared/i18n';

export const useLanguageStore = defineStore('catalog-language', () => {
  const t = i18n.global.t;
  const languages = ref<Language[]>([]);
  const selected = ref<Language | null>(null);
  const loading = ref(false);
  const error = ref<string | null>(null);
  const totalPages = ref(0);
  const currentPage = ref(1);
  const total = ref(0);

  async function fetchAll(page = 1, perPage = 20, projectId?: string): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
      const response = await languageService.list(page, perPage, projectId);
      languages.value = response.data.items;
      totalPages.value = response.data.total_pages;
      currentPage.value = response.data.page;
      total.value = response.data.total;
    } catch {
      error.value = t('common.errors.failedToLoad', { entity: t('common.entities.languages') });
    } finally {
      loading.value = false;
    }
  }

  async function remove(id: string): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
      await languageService.remove(id);
      languages.value = languages.value.filter((l) => l.id !== id);
    } catch {
      error.value = t('common.errors.failedToDelete', { entity: t('common.entities.languages') });
    } finally {
      loading.value = false;
    }
  }

  return { languages, selected, loading, error, totalPages, currentPage, total, fetchAll, remove };
});
