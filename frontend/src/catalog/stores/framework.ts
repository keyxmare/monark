import { ref } from 'vue';
import { defineStore } from 'pinia';
import type { Framework } from '@/catalog/types/framework';
import { frameworkService } from '@/catalog/services/framework.service';
import { i18n } from '@/shared/i18n';

export const useFrameworkStore = defineStore('catalog-framework', () => {
  const t = i18n.global.t;
  const frameworks = ref<Framework[]>([]);
  const selected = ref<Framework | null>(null);
  const loading = ref(false);
  const error = ref<string | null>(null);
  const totalPages = ref(0);
  const currentPage = ref(1);
  const total = ref(0);

  async function fetchAll(page = 1, perPage = 20, projectId?: string): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
      const response = await frameworkService.list(page, perPage, projectId);
      frameworks.value = response.data.items;
      totalPages.value = response.data.total_pages;
      currentPage.value = response.data.page;
      total.value = response.data.total;
    } catch {
      error.value = t('common.errors.failedToLoad', { entity: t('common.entities.frameworks') });
    } finally {
      loading.value = false;
    }
  }

  async function remove(id: string): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
      await frameworkService.remove(id);
      frameworks.value = frameworks.value.filter((f) => f.id !== id);
    } catch {
      error.value = t('common.errors.failedToDelete', { entity: t('common.entities.frameworks') });
    } finally {
      loading.value = false;
    }
  }

  return { frameworks, selected, loading, error, totalPages, currentPage, total, fetchAll, remove };
});
