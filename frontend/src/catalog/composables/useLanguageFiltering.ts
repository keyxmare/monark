import type { Ref } from 'vue';
import { computed, ref } from 'vue';
import type { Language } from '@/catalog/types/language';

export type LanguageSortField = 'name' | 'project' | 'status' | 'version';

export interface LanguageGroupedRow {
  groupIndex: number;
  groupSize: number;
  isFirstInGroup: boolean;
  lang: Language;
  projectId: string;
  projectName: string;
}

export interface UseLanguageFilteringOptions {
  languages: Ref<Language[]>;
  projectMap: Ref<Map<string, { name: string }>>;
}

export function useLanguageFiltering({ languages, projectMap }: UseLanguageFilteringOptions) {
  const search = ref('');
  const filterStatus = ref('');
  const filterLanguage = ref('');
  const sortField = ref<LanguageSortField>('project');
  const sortDir = ref<'asc' | 'desc'>('asc');

  function sortIndicator(field: LanguageSortField): string {
    if (sortField.value !== field) return '';
    return sortDir.value === 'asc' ? ' ↑' : ' ↓';
  }

  function toggleSort(field: LanguageSortField) {
    if (sortField.value === field) {
      sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
      sortField.value = field;
      sortDir.value = 'asc';
    }
  }

  const availableLanguages = computed(() => {
    const set = new Set<string>();
    for (const l of languages.value) set.add(l.name);
    return [...set].sort();
  });

  const filteredLanguages = computed(() => {
    return languages.value.filter((l) => {
      if (search.value) {
        const q = search.value.toLowerCase();
        const projName = projectMap.value.get(l.projectId)?.name ?? '';
        if (!projName.toLowerCase().includes(q) && !l.name.toLowerCase().includes(q)) return false;
      }
      if (filterStatus.value && l.maintenanceStatus !== filterStatus.value) return false;
      if (filterLanguage.value && l.name !== filterLanguage.value) return false;
      return true;
    });
  });

  const groupedLanguages = computed<LanguageGroupedRow[]>(() => {
    const groups = new Map<string, Language[]>();
    for (const l of filteredLanguages.value) {
      if (!groups.has(l.projectId)) groups.set(l.projectId, []);
      groups.get(l.projectId)!.push(l);
    }

    const dir = sortDir.value === 'asc' ? 1 : -1;
    const sorted = [...groups.entries()].sort(([keyA], [keyB]) => {
      const nameA = projectMap.value.get(keyA)?.name ?? '';
      const nameB = projectMap.value.get(keyB)?.name ?? '';
      return nameA.localeCompare(nameB) * dir;
    });

    const result: LanguageGroupedRow[] = [];
    let groupIndex = 0;
    for (const [key, langs] of sorted) {
      const projName = projectMap.value.get(key)?.name ?? key;
      langs.forEach((lang, i) => {
        result.push({
          groupIndex,
          groupSize: langs.length,
          isFirstInGroup: i === 0,
          lang,
          projectId: key,
          projectName: projName,
        });
      });
      groupIndex++;
    }
    return result;
  });

  return {
    availableLanguages,
    filteredLanguages,
    filterLanguage,
    filterStatus,
    groupedLanguages,
    search,
    sortDir,
    sortField,
    sortIndicator,
    toggleSort,
  };
}
