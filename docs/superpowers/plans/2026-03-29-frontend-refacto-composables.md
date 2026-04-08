# Frontend Refacto — Composables & Component Splitting

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Extract duplicated patterns into shared composables and split oversized components, creating a clean foundation for testing.

**Architecture:** Create 3 new shared composables (`useLocalStorage`, `useForm`, `useListFiltering`), then refactor consumers to use them. Split the 3 largest pages (TechStackList, ProjectDetail, DependencyList) into focused sub-components. Extract shared PDF utilities.

**Tech Stack:** Vue 3 + TypeScript, Pinia, Vitest, pnpm

**Commands:**
- Run tests: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend sh -c 'pnpm vitest run'`
- Run lint: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend sh -c 'pnpm lint'`
- Run single test: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend sh -c 'pnpm vitest run --reporter=verbose tests/unit/path/to/test.ts'`

**Working directory:** `/Users/keyxmare/Projects/github.com/keyxmare/monark/frontend`

---

## Task 1: Create `useLocalStorage<T>` composable

**Files:**
- Create: `src/shared/composables/useLocalStorage.ts`
- Create: `tests/unit/shared/composables/useLocalStorage.test.ts`

- [ ] **Step 1: Write the test file**

```typescript
// tests/unit/shared/composables/useLocalStorage.test.ts
import { beforeEach, describe, expect, it } from 'vitest';

import { useLocalStorage } from '@/shared/composables/useLocalStorage';

describe('useLocalStorage', () => {
  beforeEach(() => {
    localStorage.clear();
  });

  it('returns default value when key does not exist', () => {
    const value = useLocalStorage('test-key', 'default');
    expect(value.value).toBe('default');
  });

  it('reads existing value from localStorage', () => {
    localStorage.setItem('test-key', JSON.stringify('stored'));
    const value = useLocalStorage('test-key', 'default');
    expect(value.value).toBe('stored');
  });

  it('writes to localStorage when value changes', () => {
    const value = useLocalStorage('test-key', 'initial');
    value.value = 'updated';
    expect(JSON.parse(localStorage.getItem('test-key')!)).toBe('updated');
  });

  it('handles object values', () => {
    const value = useLocalStorage('obj-key', { count: 0 });
    value.value = { count: 42 };
    expect(JSON.parse(localStorage.getItem('obj-key')!)).toEqual({ count: 42 });
  });

  it('handles boolean values', () => {
    const value = useLocalStorage('bool-key', false);
    value.value = true;
    expect(JSON.parse(localStorage.getItem('bool-key')!)).toBe(true);
  });

  it('returns default when stored value is invalid JSON', () => {
    localStorage.setItem('bad-json', 'not-json');
    const value = useLocalStorage('bad-json', 'fallback');
    expect(value.value).toBe('fallback');
  });

  it('handles null default', () => {
    const value = useLocalStorage<string | null>('nullable', null);
    expect(value.value).toBeNull();
    value.value = 'set';
    expect(JSON.parse(localStorage.getItem('nullable')!)).toBe('set');
  });

  it('handles raw string mode without JSON', () => {
    localStorage.setItem('raw', 'plain-string');
    const value = useLocalStorage('raw', '', { raw: true });
    expect(value.value).toBe('plain-string');
    value.value = 'new-value';
    expect(localStorage.getItem('raw')).toBe('new-value');
  });
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend sh -c 'pnpm vitest run tests/unit/shared/composables/useLocalStorage.test.ts --reporter=verbose'`
Expected: FAIL — module not found

- [ ] **Step 3: Implement useLocalStorage**

```typescript
// src/shared/composables/useLocalStorage.ts
import { ref, watch } from 'vue';

export interface UseLocalStorageOptions {
  raw?: boolean;
}

export function useLocalStorage<T>(
  key: string,
  defaultValue: T,
  options: UseLocalStorageOptions = {},
) {
  const { raw = false } = options;

  function read(): T {
    const stored = localStorage.getItem(key);
    if (stored === null) return defaultValue;
    if (raw) return stored as T;
    try {
      return JSON.parse(stored) as T;
    } catch {
      return defaultValue;
    }
  }

  const data = ref<T>(read()) as ReturnType<typeof ref<T>>;

  watch(data, (newValue) => {
    if (newValue === null || newValue === undefined) {
      localStorage.removeItem(key);
    } else if (raw) {
      localStorage.setItem(key, String(newValue));
    } else {
      localStorage.setItem(key, JSON.stringify(newValue));
    }
  }, { deep: true });

  return data;
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend sh -c 'pnpm vitest run tests/unit/shared/composables/useLocalStorage.test.ts --reporter=verbose'`
Expected: All PASS

- [ ] **Step 5: Commit**

```bash
git add src/shared/composables/useLocalStorage.ts tests/unit/shared/composables/useLocalStorage.test.ts
git commit -m "feat(shared): add useLocalStorage composable with tests"
```

---

## Task 2: Migrate consumers to `useLocalStorage`

**Files:**
- Modify: `src/shared/composables/useLocale.ts`
- Modify: `src/shared/composables/useSidebar.ts`
- Modify: `src/shared/utils/api.ts`
- Modify: `src/app/router.ts`

- [ ] **Step 1: Refactor useLocale to use useLocalStorage**

Read `src/shared/composables/useLocale.ts`, then replace `localStorage.setItem` with `useLocalStorage`:

```typescript
// src/shared/composables/useLocale.ts
import { computed, type WritableComputedRef } from 'vue';

import { i18n } from '@/shared/i18n';
import { useLocalStorage } from '@/shared/composables/useLocalStorage';

const AVAILABLE_LOCALES = ['fr', 'en'] as const;

export type Locale = (typeof AVAILABLE_LOCALES)[number];

const storedLocale = useLocalStorage<Locale>('monark_locale', 'fr', { raw: true });
const localeRef = i18n.global.locale as unknown as WritableComputedRef<Locale>;

export function useLocale() {
  const currentLocale = computed<Locale>({
    get: () => localeRef.value,
    set: (value: Locale) => setLocale(value),
  });

  function setLocale(locale: Locale) {
    localeRef.value = locale;
    storedLocale.value = locale;
    document.documentElement.lang = locale;
  }

  return {
    availableLocales: AVAILABLE_LOCALES,
    currentLocale,
    setLocale,
  };
}
```

- [ ] **Step 2: Refactor useSidebar to use useLocalStorage**

Read `src/shared/composables/useSidebar.ts`. Add persistence for collapsed state:

```typescript
// src/shared/composables/useSidebar.ts
import { ref } from 'vue';

import { useLocalStorage } from '@/shared/composables/useLocalStorage';

const collapsed = useLocalStorage('monark_sidebar_collapsed', false);
const mobileOpen = ref(false);

export function useSidebar() {
  function toggle() {
    collapsed.value = !collapsed.value;
  }

  function toggleMobile() {
    mobileOpen.value = !mobileOpen.value;
  }

  function closeMobile() {
    mobileOpen.value = false;
  }

  return {
    closeMobile,
    collapsed,
    mobileOpen,
    toggle,
    toggleMobile,
  };
}
```

- [ ] **Step 3: Refactor api.ts and router.ts**

In `src/shared/utils/api.ts`, replace direct `localStorage.getItem('auth_token')` and `localStorage.removeItem('auth_token')` with a shared exported ref. Since api.ts is a utility (not a composable), keep localStorage direct access here but use a constant for the key:

Create a constants file:
```typescript
// src/shared/constants.ts
export const STORAGE_KEYS = {
  AUTH_TOKEN: 'auth_token',
  LOCALE: 'monark_locale',
  SIDEBAR_COLLAPSED: 'monark_sidebar_collapsed',
} as const;
```

Update `api.ts` and `router.ts` to import `STORAGE_KEYS` instead of hardcoded strings.

- [ ] **Step 4: Run all tests**

Run: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend sh -c 'pnpm vitest run --reporter=verbose'`
Expected: All 147+ tests PASS

- [ ] **Step 5: Run lint**

Run: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend sh -c 'pnpm lint'`
Expected: Clean

- [ ] **Step 6: Commit**

```bash
git add src/shared/composables/useLocale.ts src/shared/composables/useSidebar.ts src/shared/utils/api.ts src/app/router.ts src/shared/constants.ts
git commit -m "refactor(shared): migrate localStorage consumers to useLocalStorage + constants"
```

---

## Task 3: Create `useListFiltering` composable

**Files:**
- Create: `src/shared/composables/useListFiltering.ts`
- Create: `tests/unit/shared/composables/useListFiltering.test.ts`

- [ ] **Step 1: Write the test file**

```typescript
// tests/unit/shared/composables/useListFiltering.test.ts
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick, ref } from 'vue';

import { useListFiltering } from '@/shared/composables/useListFiltering';

describe('useListFiltering', () => {
  const items = ref([
    { category: 'A', id: 1, name: 'Charlie' },
    { category: 'B', id: 2, name: 'Alice' },
    { category: 'A', id: 3, name: 'Bob' },
  ]);

  beforeEach(() => {
    vi.useFakeTimers();
  });

  it('sorts by default field ascending', () => {
    const { sorted } = useListFiltering(items, { defaultSortField: 'name' });
    expect(sorted.value.map((i) => i.name)).toEqual(['Alice', 'Bob', 'Charlie']);
  });

  it('toggleSort switches to desc on same field', () => {
    const { sorted, toggleSort } = useListFiltering(items, { defaultSortField: 'name' });
    toggleSort('name');
    expect(sorted.value.map((i) => i.name)).toEqual(['Charlie', 'Bob', 'Alice']);
  });

  it('toggleSort switches to new field ascending', () => {
    const { sorted, toggleSort } = useListFiltering(items, { defaultSortField: 'name' });
    toggleSort('category');
    expect(sorted.value[0].category).toBe('A');
  });

  it('sortIndicator shows arrow for active field', () => {
    const { sortIndicator, toggleSort } = useListFiltering(items, { defaultSortField: 'name' });
    expect(sortIndicator('name')).toBe(' ↑');
    toggleSort('name');
    expect(sortIndicator('name')).toBe(' ↓');
  });

  it('sortIndicator returns empty for inactive field', () => {
    const { sortIndicator } = useListFiltering(items, { defaultSortField: 'name' });
    expect(sortIndicator('category')).toBe('');
  });

  it('search filters items with debounce', async () => {
    const { search, sorted } = useListFiltering(items, {
      defaultSortField: 'name',
      searchFields: ['name'],
    });
    search.value = 'ali';
    vi.advanceTimersByTime(300);
    await nextTick();
    expect(sorted.value).toHaveLength(1);
    expect(sorted.value[0].name).toBe('Alice');
  });

  it('search is case insensitive', async () => {
    const { search, sorted } = useListFiltering(items, {
      defaultSortField: 'name',
      searchFields: ['name'],
    });
    search.value = 'BOB';
    vi.advanceTimersByTime(300);
    await nextTick();
    expect(sorted.value).toHaveLength(1);
  });

  it('empty search returns all items', async () => {
    const { search, sorted } = useListFiltering(items, {
      defaultSortField: 'name',
      searchFields: ['name'],
    });
    search.value = 'ali';
    vi.advanceTimersByTime(300);
    await nextTick();
    search.value = '';
    vi.advanceTimersByTime(300);
    await nextTick();
    expect(sorted.value).toHaveLength(3);
  });
});
```

- [ ] **Step 2: Run test to verify it fails**

- [ ] **Step 3: Implement useListFiltering**

```typescript
// src/shared/composables/useListFiltering.ts
import type { Ref } from 'vue';

import { computed, ref, watch } from 'vue';

export interface UseListFilteringOptions {
  debounceMs?: number;
  defaultSortDir?: 'asc' | 'desc';
  defaultSortField: string;
  searchFields?: string[];
}

export function useListFiltering<T extends Record<string, unknown>>(
  items: Ref<T[]>,
  options: UseListFilteringOptions,
) {
  const { debounceMs = 300, defaultSortDir = 'asc', defaultSortField, searchFields = [] } = options;

  const sortField = ref(defaultSortField);
  const sortDir = ref<'asc' | 'desc'>(defaultSortDir);
  const search = ref('');
  const debouncedSearch = ref('');
  let debounceTimer: ReturnType<typeof setTimeout> | null = null;

  watch(search, (val) => {
    if (debounceTimer) clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
      debouncedSearch.value = val;
    }, debounceMs);
  });

  function toggleSort(field: string) {
    if (sortField.value === field) {
      sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
      sortField.value = field;
      sortDir.value = 'asc';
    }
  }

  function sortIndicator(field: string): string {
    if (sortField.value !== field) return '';
    return sortDir.value === 'asc' ? ' ↑' : ' ↓';
  }

  const filtered = computed(() => {
    if (!debouncedSearch.value || searchFields.length === 0) return items.value;
    const term = debouncedSearch.value.toLowerCase();
    return items.value.filter((item) =>
      searchFields.some((field) => {
        const val = item[field];
        return typeof val === 'string' && val.toLowerCase().includes(term);
      }),
    );
  });

  const sorted = computed(() => {
    const arr = [...filtered.value];
    const field = sortField.value;
    const dir = sortDir.value === 'asc' ? 1 : -1;
    return arr.sort((a, b) => {
      const aVal = a[field];
      const bVal = b[field];
      if (aVal === bVal) return 0;
      if (aVal === null || aVal === undefined) return 1;
      if (bVal === null || bVal === undefined) return -1;
      if (typeof aVal === 'string' && typeof bVal === 'string') {
        return aVal.localeCompare(bVal) * dir;
      }
      return ((aVal as number) - (bVal as number)) * dir;
    });
  });

  return {
    debouncedSearch,
    filtered,
    search,
    sortDir,
    sortField,
    sortIndicator,
    sorted,
    toggleSort,
  };
}
```

- [ ] **Step 4: Run test to verify it passes**

- [ ] **Step 5: Commit**

```bash
git add src/shared/composables/useListFiltering.ts tests/unit/shared/composables/useListFiltering.test.ts
git commit -m "feat(shared): add useListFiltering composable with debounced search and sort"
```

---

## Task 4: Create `useForm<T>` composable

**Files:**
- Create: `src/shared/composables/useForm.ts`
- Create: `tests/unit/shared/composables/useForm.test.ts`

- [ ] **Step 1: Write the test file**

```typescript
// tests/unit/shared/composables/useForm.test.ts
import { describe, expect, it, vi } from 'vitest';

import { useForm } from '@/shared/composables/useForm';

describe('useForm', () => {
  const initialValues = { email: '', name: '' };

  it('initializes with provided values', () => {
    const { fields } = useForm(initialValues);
    expect(fields.name).toBe('');
    expect(fields.email).toBe('');
  });

  it('tracks touched state per field', () => {
    const { touch, touched } = useForm(initialValues);
    expect(touched.name).toBe(false);
    touch('name');
    expect(touched.name).toBe(true);
    expect(touched.email).toBe(false);
  });

  it('touchAll marks all fields as touched', () => {
    const { touchAll, touched } = useForm(initialValues);
    touchAll();
    expect(touched.name).toBe(true);
    expect(touched.email).toBe(true);
  });

  it('reports required field errors when touched and empty', () => {
    const { errors, touchAll } = useForm(initialValues, {
      required: ['name', 'email'],
    });
    touchAll();
    expect(errors.value.name).toBe(true);
    expect(errors.value.email).toBe(true);
  });

  it('clears error when field is filled', () => {
    const { errors, fields, touchAll } = useForm(initialValues, {
      required: ['name'],
    });
    touchAll();
    expect(errors.value.name).toBe(true);
    fields.name = 'John';
    expect(errors.value.name).toBe(false);
  });

  it('isValid is true when all required fields are filled', () => {
    const { fields, isValid } = useForm(initialValues, {
      required: ['name'],
    });
    fields.name = 'John';
    expect(isValid.value).toBe(true);
  });

  it('isValid is false when a required field is empty', () => {
    const { isValid } = useForm(initialValues, {
      required: ['name'],
    });
    expect(isValid.value).toBe(false);
  });

  it('handleSubmit calls callback when valid', async () => {
    const callback = vi.fn();
    const { fields, handleSubmit } = useForm(initialValues, {
      required: ['name'],
    });
    fields.name = 'John';
    await handleSubmit(callback);
    expect(callback).toHaveBeenCalled();
  });

  it('handleSubmit does not call callback when invalid', async () => {
    const callback = vi.fn();
    const { handleSubmit, touched } = useForm(initialValues, {
      required: ['name'],
    });
    await handleSubmit(callback);
    expect(callback).not.toHaveBeenCalled();
    expect(touched.name).toBe(true);
  });

  it('reset restores initial values and clears touched', () => {
    const { fields, reset, touch, touched } = useForm(initialValues);
    fields.name = 'John';
    touch('name');
    reset();
    expect(fields.name).toBe('');
    expect(touched.name).toBe(false);
  });

  it('reset with new values uses those instead', () => {
    const { fields, reset } = useForm(initialValues);
    reset({ email: 'new@test.com', name: 'New' });
    expect(fields.name).toBe('New');
    expect(fields.email).toBe('new@test.com');
  });
});
```

- [ ] **Step 2: Run test to verify it fails**

- [ ] **Step 3: Implement useForm**

```typescript
// src/shared/composables/useForm.ts
import { computed, reactive } from 'vue';

export interface UseFormOptions<T> {
  required?: (keyof T)[];
}

export function useForm<T extends Record<string, unknown>>(
  initialValues: T,
  options: UseFormOptions<T> = {},
) {
  const { required = [] } = options;

  const fields = reactive({ ...initialValues }) as T;
  const touched = reactive(
    Object.fromEntries(Object.keys(initialValues).map((k) => [k, false])),
  ) as Record<keyof T, boolean>;

  function touch(field: keyof T) {
    touched[field] = true;
  }

  function touchAll() {
    for (const key of Object.keys(touched)) {
      touched[key as keyof T] = true;
    }
  }

  const errors = computed(() => {
    const result = {} as Record<keyof T, boolean>;
    for (const key of Object.keys(fields as Record<string, unknown>)) {
      const k = key as keyof T;
      const isEmpty = fields[k] === '' || fields[k] === null || fields[k] === undefined;
      result[k] = touched[k] && required.includes(k) && isEmpty;
    }
    return result;
  });

  const isValid = computed(() => {
    return required.every((k) => {
      const val = fields[k];
      return val !== '' && val !== null && val !== undefined;
    });
  });

  async function handleSubmit(callback: () => Promise<void> | void) {
    touchAll();
    if (!isValid.value) return;
    await callback();
  }

  function reset(values?: T) {
    const source = values ?? initialValues;
    for (const key of Object.keys(source as Record<string, unknown>)) {
      const k = key as keyof T;
      (fields as Record<string, unknown>)[k as string] = source[k];
      touched[k] = false;
    }
  }

  return { errors, fields, handleSubmit, isValid, reset, touch, touchAll, touched };
}
```

- [ ] **Step 4: Run test to verify it passes**

- [ ] **Step 5: Commit**

```bash
git add src/shared/composables/useForm.ts tests/unit/shared/composables/useForm.test.ts
git commit -m "feat(shared): add useForm composable with validation, touched tracking, and reset"
```

---

## Task 5: Split DependencyList.vue into sub-components

**Files:**
- Create: `src/dependency/components/DependencyFilters.vue`
- Create: `src/dependency/components/DependencyHealthScore.vue`
- Modify: `src/dependency/pages/DependencyList.vue`

- [ ] **Step 1: Read DependencyList.vue completely**

Read `src/dependency/pages/DependencyList.vue` to understand all sections.

- [ ] **Step 2: Extract DependencyFilters.vue**

Extract the filter bar (search, package manager, type, status, project selects) into its own component. The component receives filter refs via `v-model` and emits changes.

- [ ] **Step 3: Extract DependencyHealthScore.vue**

Extract the health score card and gap statistics section into its own component. Receives `healthScore` and `depGapStats` as props.

- [ ] **Step 4: Simplify DependencyList.vue**

The page component should now be a thin orchestrator: store loading, sub-component composition, pagination.

- [ ] **Step 5: Run all tests + lint**

Run: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend sh -c 'pnpm vitest run && pnpm lint'`
Expected: All PASS

- [ ] **Step 6: Commit**

```bash
git add src/dependency/components/ src/dependency/pages/DependencyList.vue
git commit -m "refactor(dependency): split DependencyList into DependencyFilters + DependencyHealthScore"
```

---

## Task 6: Split ProjectDetail.vue into tab components

**Files:**
- Create: `src/catalog/components/ProjectTechStacksTab.vue`
- Create: `src/catalog/components/ProjectDependenciesTab.vue`
- Create: `src/catalog/components/ProjectMergeRequestsTab.vue`
- Modify: `src/catalog/pages/ProjectDetail.vue`

- [ ] **Step 1: Read ProjectDetail.vue completely**

Read `src/catalog/pages/ProjectDetail.vue` to identify the 3 tab sections.

- [ ] **Step 2: Extract ProjectTechStacksTab.vue**

Extract the tech stacks tab content. Receives `projectId` as prop, uses `techStackStore` internally.

- [ ] **Step 3: Extract ProjectDependenciesTab.vue**

Extract the dependencies tab content. Receives `projectId` as prop, uses `dependencyStore` internally.

- [ ] **Step 4: Extract ProjectMergeRequestsTab.vue**

Extract the merge requests tab content. Receives `projectId` as prop, uses `mergeRequestStore` internally.

- [ ] **Step 5: Simplify ProjectDetail.vue**

Page becomes: header, tab navigation, `<component :is>` or `v-if` for tab content, delete confirmation.

- [ ] **Step 6: Run all tests + lint**

- [ ] **Step 7: Commit**

```bash
git add src/catalog/components/Project*.vue src/catalog/pages/ProjectDetail.vue
git commit -m "refactor(catalog): split ProjectDetail into tab sub-components"
```

---

## Task 7: Split TechStackList.vue into sub-components + composable

**Files:**
- Create: `src/catalog/composables/useTechStackGrouping.ts`
- Create: `tests/unit/catalog/composables/useTechStackGrouping.test.ts`
- Create: `src/catalog/components/TechStackFilters.vue`
- Create: `src/catalog/components/TechStackTable.vue`
- Modify: `src/catalog/pages/TechStackList.vue`

- [ ] **Step 1: Read TechStackList.vue completely**

Read `src/catalog/pages/TechStackList.vue` to identify grouping logic, filtering, and table rendering.

- [ ] **Step 2: Write test for useTechStackGrouping**

Test the grouping/aggregation logic: group by framework, by provider, by status. Test computed aggregations (outdated count, vulnerability count per group).

- [ ] **Step 3: Implement useTechStackGrouping**

Extract the computed properties for grouping, aggregation, and statistics from TechStackList into a composable.

- [ ] **Step 4: Run test to verify it passes**

- [ ] **Step 5: Extract TechStackFilters.vue**

Extract filter controls (framework select, provider select, status, grouping mode).

- [ ] **Step 6: Extract TechStackTable.vue**

Extract the table rendering with sort headers and row display.

- [ ] **Step 7: Simplify TechStackList.vue**

Page becomes orchestrator: store loading, composable usage, sub-component composition, pagination, PDF export trigger.

- [ ] **Step 8: Run all tests + lint**

- [ ] **Step 9: Commit**

```bash
git add src/catalog/composables/useTechStackGrouping.ts tests/unit/catalog/composables/useTechStackGrouping.test.ts src/catalog/components/TechStack*.vue src/catalog/pages/TechStackList.vue
git commit -m "refactor(catalog): split TechStackList into sub-components + useTechStackGrouping composable"
```

---

## Task 8: Extract shared PDF utilities

**Files:**
- Create: `src/shared/utils/pdfExport.ts`
- Modify: `src/catalog/services/techStackPdfExport.ts`
- Modify: `src/dependency/services/dependencyPdfExport.ts`

- [ ] **Step 1: Read both PDF export files**

Read `src/catalog/services/techStackPdfExport.ts` and `src/dependency/services/dependencyPdfExport.ts` to identify shared patterns (document creation, header, footer, table styling, branding).

- [ ] **Step 2: Create shared PDF utilities**

Extract common functions:
- `createPdfDocument()` — creates jsPDF instance with standard settings
- `addPdfHeader(doc, title, subtitle?)` — adds branded header
- `addPdfFooter(doc)` — adds page numbers and branding
- `pdfTableStyles()` — returns standard autoTable theme/styles

- [ ] **Step 3: Refactor techStackPdfExport.ts to use shared utilities**

Replace duplicated setup/header/footer code with shared function calls.

- [ ] **Step 4: Refactor dependencyPdfExport.ts to use shared utilities**

Same refactoring.

- [ ] **Step 5: Run all tests + lint**

- [ ] **Step 6: Commit**

```bash
git add src/shared/utils/pdfExport.ts src/catalog/services/techStackPdfExport.ts src/dependency/services/dependencyPdfExport.ts
git commit -m "refactor(shared): extract common PDF export utilities"
```

---

## Task 9: Final validation

- [ ] **Step 1: Run full test suite**

Run: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend sh -c 'pnpm vitest run'`
Expected: All tests PASS (original 147 + new composable tests)

- [ ] **Step 2: Run lint**

Run: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend sh -c 'pnpm lint && pnpm format:check'`
Expected: Clean

- [ ] **Step 3: Verify no file exceeds 300 lines**

Run: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend sh -c 'find src -name "*.vue" -o -name "*.ts" | xargs wc -l | sort -rn | head -20'`
Expected: No source file > 300 lines (except useFrameworkLts.ts which is out of scope)

- [ ] **Step 4: Commit any remaining changes**

```bash
git add -A
git commit -m "refactor(frontend): complete composable extraction and component splitting"
```
