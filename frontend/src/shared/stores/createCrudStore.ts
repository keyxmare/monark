import { ref } from 'vue';
import { defineStore } from 'pinia';
import type { CrudService } from '@/shared/types/crud';
import { i18n } from '@/shared/i18n';

export function createCrudStore<
  T extends { id: string },
  TCreate = Partial<T>,
  TUpdate = Partial<T>,
>(name: string, service: CrudService<T, TCreate, TUpdate>, entityKey = 'items') {
  return defineStore(name, () => {
    const t = i18n.global.t;
    const items = ref<T[]>([]) as { value: T[] };
    const current = ref<T | null>(null) as { value: T | null };
    const loading = ref(false);
    const error = ref<string | null>(null);
    const totalPages = ref(0);
    const currentPage = ref(0);
    const total = ref(0);

    async function fetchAll(page = 1, perPage = 20): Promise<void> {
      loading.value = true;
      error.value = null;

      try {
        const response = await service.list(page, perPage);
        items.value = response.data.items;
        totalPages.value = response.data.total_pages;
        currentPage.value = response.data.page;
        total.value = response.data.total;
      } catch {
        error.value = t('common.errors.failedToLoad', { entity: entityKey });
      } finally {
        loading.value = false;
      }
    }

    async function fetchOne(id: string): Promise<void> {
      loading.value = true;
      error.value = null;

      try {
        const response = await service.get(id);
        current.value = response.data;
      } catch {
        error.value = t('common.errors.failedToLoad', { entity: entityKey });
      } finally {
        loading.value = false;
      }
    }

    async function create(data: TCreate): Promise<T> {
      loading.value = true;
      error.value = null;

      try {
        const response = await service.create(data);
        items.value.unshift(response.data);
        return response.data;
      } catch {
        error.value = t('common.errors.failedToCreate', { entity: entityKey });
        throw new Error(error.value);
      } finally {
        loading.value = false;
      }
    }

    async function update(id: string, data: TUpdate): Promise<T> {
      loading.value = true;
      error.value = null;

      try {
        const response = await service.update(id, data);
        current.value = response.data;
        const index = items.value.findIndex((item) => item.id === id);
        if (index !== -1) {
          items.value[index] = response.data;
        }
        return response.data;
      } catch {
        error.value = t('common.errors.failedToUpdate', { entity: entityKey });
        throw new Error(error.value);
      } finally {
        loading.value = false;
      }
    }

    async function remove(id: string): Promise<void> {
      loading.value = true;
      error.value = null;

      try {
        await service.remove(id);
        items.value = items.value.filter((item) => item.id !== id);
      } catch {
        error.value = t('common.errors.failedToDelete', { entity: entityKey });
        throw new Error(error.value);
      } finally {
        loading.value = false;
      }
    }

    return {
      items,
      current,
      loading,
      error,
      totalPages,
      currentPage,
      total,
      fetchAll,
      fetchOne,
      create,
      update,
      remove,
    };
  });
}
