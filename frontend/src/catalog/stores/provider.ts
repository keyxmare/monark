import { ref } from 'vue';
import { defineStore } from 'pinia';
import type {
  CreateProviderInput,
  ImportProjectsInput,
  Provider,
  RemoteProject,
  UpdateProviderInput,
} from '@/catalog/types/provider';
import type { Project } from '@/catalog/types/project';
import { providerService } from '@/catalog/services/provider.service';
import { i18n } from '@/shared/i18n';

export const useProviderStore = defineStore('catalog-provider', () => {
  const t = i18n.global.t;
  const providers = ref<Provider[]>([]);
  const selected = ref<Provider | null>(null);
  const remoteProjects = ref<RemoteProject[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);
  const totalPages = ref(0);
  const currentPage = ref(1);
  const total = ref(0);
  const remoteProjectsTotalPages = ref(0);
  const remoteProjectsCurrentPage = ref(1);
  const remoteProjectsTotal = ref(0);
  const remoteProjectsError = ref<string | null>(null);

  async function fetchAll(page = 1, perPage = 20): Promise<void> {
    loading.value = true;
    error.value = null;

    try {
      const response = await providerService.list(page, perPage);
      providers.value = response.data.items;
      totalPages.value = response.data.total_pages;
      currentPage.value = response.data.page;
      total.value = response.data.total;
    } catch {
      error.value = t('common.errors.failedToLoad', { entity: t('common.entities.providers') });
    } finally {
      loading.value = false;
    }
  }

  async function fetchOne(id: string): Promise<void> {
    loading.value = true;
    error.value = null;

    try {
      const response = await providerService.get(id);
      selected.value = response.data;
    } catch {
      error.value = t('common.errors.failedToLoad', { entity: t('common.entities.providers') });
    } finally {
      loading.value = false;
    }
  }

  async function create(data: CreateProviderInput): Promise<Provider> {
    loading.value = true;
    error.value = null;

    try {
      const response = await providerService.create(data);
      providers.value.unshift(response.data);
      return response.data;
    } catch {
      error.value = t('common.errors.failedToCreate', { entity: t('common.entities.providers') });
      throw new Error(error.value);
    } finally {
      loading.value = false;
    }
  }

  async function update(id: string, data: UpdateProviderInput): Promise<void> {
    loading.value = true;
    error.value = null;

    try {
      const response = await providerService.update(id, data);
      selected.value = response.data;
      const index = providers.value.findIndex((p) => p.id === id);
      if (index !== -1) {
        providers.value[index] = response.data;
      }
    } catch {
      error.value = t('common.errors.failedToUpdate', { entity: t('common.entities.providers') });
    } finally {
      loading.value = false;
    }
  }

  async function remove(id: string): Promise<void> {
    loading.value = true;
    error.value = null;

    try {
      await providerService.remove(id);
      providers.value = providers.value.filter((p) => p.id !== id);
    } catch {
      error.value = t('common.errors.failedToDelete', { entity: t('common.entities.providers') });
    } finally {
      loading.value = false;
    }
  }

  async function testConnection(id: string): Promise<boolean> {
    loading.value = true;
    error.value = null;

    try {
      const response = await providerService.testConnection(id);
      const updatedProvider = response.data;
      const connected = updatedProvider.status === 'connected';
      if (selected.value && selected.value.id === id) {
        selected.value.status = updatedProvider.status;
      }
      const index = providers.value.findIndex((p) => p.id === id);
      if (index !== -1) {
        providers.value[index].status = updatedProvider.status;
      }
      return connected;
    } catch {
      error.value = t('common.errors.failedToTestConnection');
      return false;
    } finally {
      loading.value = false;
    }
  }

  async function fetchRemoteProjects(
    id: string,
    page = 1,
    perPage = 20,
    params?: { search?: string; sort?: string; sortDir?: string; visibility?: string },
  ): Promise<void> {
    remoteProjectsError.value = null;

    try {
      const response = await providerService.listRemoteProjects(id, page, perPage, params);
      const data = response.data;
      remoteProjects.value = data.items;
      remoteProjectsTotalPages.value = data.total_pages;
      remoteProjectsCurrentPage.value = data.page;
      remoteProjectsTotal.value = data.total;
    } catch {
      remoteProjectsError.value = t('common.errors.failedToLoad', {
        entity: t('common.entities.remoteProjects'),
      });
      if (selected.value) {
        selected.value = { ...selected.value, status: 'error' };
      }
      const index = providers.value.findIndex((p) => p.id === id);
      if (index !== -1) {
        providers.value[index] = { ...providers.value[index], status: 'error' };
      }
    }
  }

  async function importProjects(id: string, data: ImportProjectsInput): Promise<Project[]> {
    loading.value = true;
    error.value = null;

    try {
      const response = await providerService.importProjects(id, data);
      remoteProjects.value = remoteProjects.value.map((rp) =>
        data.projects.some((p) => p.externalId === rp.externalId)
          ? { ...rp, alreadyImported: true }
          : rp,
      );
      return response.data;
    } catch {
      error.value = t('common.errors.failedToImportProjects');
      throw new Error(error.value);
    } finally {
      loading.value = false;
    }
  }

  async function syncAll(
    id: string,
    force = false,
    projectIds?: string[],
  ): Promise<{ id: string; projectsCount: number }> {
    const response = await providerService.syncAll(id, force, projectIds);
    return { id: response.data.id, projectsCount: response.data.projectsCount };
  }

  async function syncAllGlobal(force = false): Promise<{ id: string; projectsCount: number }> {
    const response = await providerService.syncAllGlobal(force);
    return { id: response.data.id, projectsCount: response.data.projectsCount };
  }

  return {
    providers,
    selected,
    remoteProjects,
    loading,
    error,
    totalPages,
    currentPage,
    total,
    remoteProjectsTotalPages,
    remoteProjectsCurrentPage,
    remoteProjectsTotal,
    remoteProjectsError,
    fetchAll,
    fetchOne,
    create,
    update,
    remove,
    testConnection,
    fetchRemoteProjects,
    importProjects,
    syncAll,
    syncAllGlobal,
  };
});
