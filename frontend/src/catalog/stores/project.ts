import { ref } from 'vue';
import { defineStore } from 'pinia';
import type {
  CreateProjectInput,
  Project,
  ScanResult,
  UpdateProjectInput,
} from '@/catalog/types/project';
import { projectService } from '@/catalog/services/project.service';
import { i18n } from '@/shared/i18n';

export const useProjectStore = defineStore('catalog-project', () => {
  const t = i18n.global.t;
  const projects = ref<Project[]>([]);
  const selected = ref<Project | null>(null);
  const loading = ref(false);
  const scanning = ref(false);
  const error = ref<string | null>(null);
  const totalPages = ref(0);
  const currentPage = ref(1);
  const total = ref(0);
  const scanResult = ref<ScanResult | null>(null);
  const branches = ref<string[]>([]);

  async function fetchAll(page = 1, perPage = 20): Promise<void> {
    loading.value = true;
    error.value = null;

    try {
      const response = await projectService.list(page, perPage);
      projects.value = response.data.items;
      totalPages.value = response.data.total_pages;
      currentPage.value = response.data.page;
      total.value = response.data.total;
    } catch {
      error.value = t('common.errors.failedToLoad', { entity: t('common.entities.projects') });
    } finally {
      loading.value = false;
    }
  }

  async function fetchOne(id: string): Promise<void> {
    loading.value = true;
    error.value = null;

    try {
      const response = await projectService.get(id);
      selected.value = response.data;
    } catch {
      error.value = t('common.errors.failedToLoad', { entity: t('common.entities.projects') });
    } finally {
      loading.value = false;
    }
  }

  async function create(data: CreateProjectInput): Promise<Project> {
    loading.value = true;
    error.value = null;

    try {
      const response = await projectService.create(data);
      projects.value.unshift(response.data);
      return response.data;
    } catch {
      error.value = t('common.errors.failedToCreate', { entity: t('common.entities.projects') });
      throw new Error(error.value);
    } finally {
      loading.value = false;
    }
  }

  async function update(id: string, data: UpdateProjectInput): Promise<void> {
    loading.value = true;
    error.value = null;

    try {
      const response = await projectService.update(id, data);
      selected.value = response.data;
      const index = projects.value.findIndex((p) => p.id === id);
      if (index !== -1) {
        projects.value[index] = response.data;
      }
    } catch {
      error.value = t('common.errors.failedToUpdate', { entity: t('common.entities.projects') });
    } finally {
      loading.value = false;
    }
  }

  async function remove(id: string): Promise<void> {
    loading.value = true;
    error.value = null;

    try {
      await projectService.remove(id);
      projects.value = projects.value.filter((p) => p.id !== id);
    } catch {
      error.value = t('common.errors.failedToDelete', { entity: t('common.entities.projects') });
    } finally {
      loading.value = false;
    }
  }

  async function scan(id: string): Promise<ScanResult> {
    scanning.value = true;
    error.value = null;
    scanResult.value = null;

    try {
      const response = await projectService.scan(id);
      scanResult.value = response.data;
      return response.data;
    } catch {
      error.value = t('common.errors.failedToScan', { entity: t('common.entities.projects') });
      throw new Error(error.value);
    } finally {
      scanning.value = false;
    }
  }

  async function fetchBranches(id: string): Promise<void> {
    try {
      branches.value = await projectService.listBranches(id);
    } catch {
      branches.value = [];
    }
  }

  return {
    projects,
    selected,
    loading,
    scanning,
    error,
    totalPages,
    currentPage,
    total,
    scanResult,
    branches,
    fetchAll,
    fetchOne,
    create,
    update,
    remove,
    scan,
    fetchBranches,
  };
});
