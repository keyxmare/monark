import { computed, ref } from 'vue';
import { defineStore } from 'pinia';
import type {
  CreateDependencyInput,
  Dependency,
  UpdateDependencyInput,
} from '@/dependency/types/dependency';
import { dependencyService } from '@/dependency/services/dependency.service';
import { createCrudStore } from '@/shared/stores/createCrudStore';

const projectIdFilter = ref<string | undefined>(undefined);

const useBaseStore = createCrudStore<Dependency, CreateDependencyInput, UpdateDependencyInput>(
  '_dependency_base',
  {
    get list() {
      return (page?: number, perPage?: number) =>
        dependencyService.list(page, perPage, projectIdFilter.value);
    },
    get get() {
      return dependencyService.get.bind(dependencyService);
    },
    get create() {
      return dependencyService.create.bind(dependencyService);
    },
    get update() {
      return dependencyService.update.bind(dependencyService);
    },
    get remove() {
      return dependencyService.remove.bind(dependencyService);
    },
  },
  'dependencies',
);

export const useDependencyStore = defineStore('dependency', () => {
  const base = useBaseStore();

  const items = computed(() => base.items);
  const current = computed(() => base.current);
  const loading = computed(() => base.loading);
  const error = computed(() => base.error);
  const totalPages = computed(() => base.totalPages);
  const currentPage = computed(() => base.currentPage);
  const total = computed(() => base.total);
  const dependencies = computed(() => base.items);
  const selectedDependency = computed(() => base.current);

  async function fetchAll(page = 1, perPage = 20, projectId?: string): Promise<void> {
    projectIdFilter.value = projectId;
    return base.fetchAll(page, perPage);
  }

  return {
    items,
    current,
    loading,
    error,
    totalPages,
    currentPage,
    total,
    dependencies,
    selectedDependency,
    fetchAll,
    fetchOne: (id: string) => base.fetchOne(id),
    create: (data: CreateDependencyInput) => base.create(data),
    update: (id: string, data: UpdateDependencyInput) => base.update(id, data),
    remove: (id: string) => base.remove(id),
  };
});
