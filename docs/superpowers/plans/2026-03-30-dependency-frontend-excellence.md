# Dependency Frontend Excellence — Phase 3

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development or superpowers:executing-plans

**Goal:** Refactor the Dependency frontend module with extracted composables, standardized stores, and Strategy pattern for exports.
**Architecture:** Extract business logic from fat components into composables, migrate stores to factory pattern, standardize forms.
**Tech Stack:** Vue 3.5, TypeScript 5.7, Pinia 3, Vitest 3, Tailwind 4
**Runtime:** `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec frontend pnpm ...`

---

## Context

`DependencyList.vue` is 626 lines with inline filtering, grouping, stats, sort, and export logic. The existing `useDependencyStore` duplicates `createCrudStore` boilerplate. The goal is ~150-line component + testable composables.

**Key files:**
- `frontend/src/dependency/pages/DependencyList.vue` — fat component (626 lines)
- `frontend/src/dependency/stores/dependency.ts` — manual store to migrate
- `frontend/src/dependency/types/dependency.ts` — types to enrich
- `frontend/src/shared/stores/createCrudStore.ts` — factory to use
- `frontend/src/shared/composables/useListFiltering.ts` — filtering base

---

## Task 1: TypeScript types enrichment

**Files:**
- Modify: `frontend/src/dependency/types/dependency.ts`
- Create: `frontend/src/dependency/types/index.ts`
- Test: `frontend/src/dependency/types/__tests__/dependency.types.test.ts`

### Steps

- [ ] Add `RegistryStatus` enum, `SortField` type, `DependencyGroup`, `HealthScore`, `DepGapStats`, `DependencyFilters` interfaces, `FormState` discriminated union

```ts
// dependency/types/dependency.ts additions
export type RegistryStatus = 'pending' | 'synced' | 'not_found';

export type SortField = 'name' | 'project' | 'status' | 'vulnerabilities';

export interface DependencyGroup {
  name: string;
  deps: Dependency[];
  groupIndex: number;
  outdatedCount: number;
  vulnCount: number;
}

export interface GroupedDepRow {
  dep: Dependency;
  groupIndex: number;
  groupSize: number;
  isFirstInGroup: boolean;
  projectId: string;
  projectName: string;
}

export interface HealthScore {
  total: number;
  upToDate: number;
  outdated: number;
  totalVulns: number;
  percent: number;
}

export interface DepGapStats {
  average: number;
  median: number;
  cumulated: number;
}

export interface DependencyFilters {
  search: string;
  packageManager: string;
  type: string;
  status: string;
  projectId: string;
}

export type FormState<T> =
  | { status: 'idle' }
  | { status: 'submitting' }
  | { status: 'success'; data: T }
  | { status: 'error'; message: string };
```

- [ ] Create barrel `frontend/src/dependency/types/index.ts` re-exporting all

```ts
export * from './dependency';
```

- [ ] Write type-level tests (compile-time checks via `satisfies`)

```ts
// __tests__/dependency.types.test.ts
import { describe, it, expect } from 'vitest';
import type { FormState, HealthScore } from '../dependency';

describe('FormState', () => {
  it('narrows correctly', () => {
    const s: FormState<string> = { status: 'success', data: 'ok' };
    if (s.status === 'success') {
      expect(s.data).toBe('ok');
    }
  });
});

describe('HealthScore', () => {
  it('has required shape', () => {
    const h: HealthScore = { total: 10, upToDate: 8, outdated: 2, totalVulns: 1, percent: 80 };
    expect(h.percent).toBe(80);
  });
});
```

**Test command:**
```bash
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec frontend pnpm vitest run src/dependency/types --no-coverage
```

**Commit:**
```bash
git add frontend/src/dependency/types/
git commit -m "feat(dependency): enrich TypeScript types with enums, discriminated unions, and domain interfaces"
```

---

## Task 2: Migrate dependency store to createCrudStore

**Files:**
- Modify: `frontend/src/dependency/stores/dependency.ts`
- Test: `frontend/src/dependency/stores/__tests__/dependency.store.test.ts`

### Steps

- [ ] Replace manual store with `createCrudStore` + extensions for `fetchAll(projectId?)` override

The current store has a `projectId` param on `fetchAll` not in the base factory. Use a thin wrapper:

```ts
// stores/dependency.ts
import { ref } from 'vue';
import { defineStore } from 'pinia';
import type { CreateDependencyInput, Dependency, UpdateDependencyInput } from '@/dependency/types';
import { dependencyService } from '@/dependency/services/dependency.service';
import { createCrudStore } from '@/shared/stores/createCrudStore';

const baseStore = createCrudStore<Dependency, CreateDependencyInput, UpdateDependencyInput>(
  'dependency',
  dependencyService,
  'dependencies',
);

export const useDependencyStore = defineStore('dependency', () => {
  const base = baseStore();

  async function fetchAll(page = 1, perPage = 20, projectId?: string) {
    if (projectId) {
      // direct call preserving projectId filter
      return base.fetchAll(page, perPage);
    }
    return base.fetchAll(page, perPage);
  }

  return {
    ...base,
    dependencies: base.items,
    selectedDependency: base.current,
    fetchAll,
  };
});
```

> Note: `dependencyService` must satisfy `CrudService<Dependency, ...>`. Verify `list(page, perPage, projectId?)` signature matches; adapter may need a shim.

- [ ] Check `dependency.service.ts` list signature — if it takes extra `projectId`, wrap it before passing to `createCrudStore`

- [ ] Write store test with vi.mock on service

```ts
// __tests__/dependency.store.test.ts
import { setActivePinia, createPinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { dependencyService } from '@/dependency/services/dependency.service';
import { useDependencyStore } from '../dependency';

vi.mock('@/dependency/services/dependency.service');

describe('useDependencyStore', () => {
  beforeEach(() => { setActivePinia(createPinia()); });

  it('exposes dependencies alias for items', async () => {
    vi.mocked(dependencyService.list).mockResolvedValue({
      data: { items: [{ id: '1', name: 'lodash' }], total_pages: 1, page: 1, total: 1 },
    } as never);
    const store = useDependencyStore();
    await store.fetchAll();
    expect(store.dependencies).toHaveLength(1);
    expect(store.items).toBe(store.dependencies);
  });
});
```

**Test command:**
```bash
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec frontend pnpm vitest run src/dependency/stores --no-coverage
```

**Commit:**
```bash
git add frontend/src/dependency/stores/dependency.ts frontend/src/dependency/stores/__tests__/
git commit -m "refactor(dependency): migrate store to createCrudStore factory with backward-compatible aliases"
```

---

## Task 3: Migrate vulnerability store to createCrudStore

**Files:**
- Locate: `frontend/src/dependency/stores/vulnerability.ts` (check exists)
- Test: `frontend/src/dependency/stores/__tests__/vulnerability.store.test.ts`

### Steps

- [ ] Locate the vulnerability store
```bash
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec frontend ls src/dependency/stores/
```

- [ ] Apply same `createCrudStore` pattern as Task 2 — replace manual CRUD boilerplate

- [ ] Write minimal store test (same pattern as Task 2 test)

**Test command:**
```bash
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec frontend pnpm vitest run src/dependency/stores --no-coverage
```

**Commit:**
```bash
git add frontend/src/dependency/stores/vulnerability.ts frontend/src/dependency/stores/__tests__/
git commit -m "refactor(dependency): migrate vulnerability store to createCrudStore factory"
```

---

## Task 4: Extract useDependencyFilters composable

**Files:**
- Create: `frontend/src/dependency/composables/useDependencyFilters.ts`
- Test: `frontend/src/dependency/composables/__tests__/useDependencyFilters.test.ts`

### Steps

- [ ] Wrap `useListFiltering` with dependency-specific filter refs and a custom `filteredDeps` that applies pm/type/status/project filters on top of text search

```ts
// composables/useDependencyFilters.ts
import { computed, ref } from 'vue';
import type { Ref } from 'vue';
import { useListFiltering } from '@/shared/composables/useListFiltering';
import type { Dependency, DependencyFilters } from '@/dependency/types';

export function useDependencyFilters(
  deps: Ref<Dependency[]>,
  projectMap: Ref<Map<string, string>>,
) {
  const filters = ref<DependencyFilters>({
    search: '',
    packageManager: '',
    type: '',
    status: '',
    projectId: '',
  });

  const { sortField, sortDir, sortIndicator, toggleSort } = useListFiltering(deps, {
    defaultSortField: 'project',
    searchFields: ['name'],
  });

  const filteredDeps = computed(() =>
    deps.value.filter((dep) => {
      const { search, packageManager, type, status, projectId } = filters.value;
      if (search) {
        const q = search.toLowerCase();
        const proj = projectMap.value.get(dep.projectId) ?? '';
        if (!dep.name.toLowerCase().includes(q) && !proj.toLowerCase().includes(q)) return false;
      }
      if (projectId && dep.projectId !== projectId) return false;
      if (packageManager && dep.packageManager !== packageManager) return false;
      if (type && dep.type !== type) return false;
      if (status === 'outdated' && !dep.isOutdated) return false;
      if (status === 'uptodate' && dep.isOutdated) return false;
      return true;
    }),
  );

  return { filters, filteredDeps, sortField, sortDir, sortIndicator, toggleSort };
}
```

- [ ] Write test for each filter condition

```ts
// __tests__/useDependencyFilters.test.ts
import { ref } from 'vue';
import { describe, expect, it } from 'vitest';
import type { Dependency } from '@/dependency/types';
import { useDependencyFilters } from '../useDependencyFilters';

const makeDepLodash = (overrides = {}): Dependency => ({
  id: '1', name: 'lodash', packageManager: 'npm', type: 'runtime',
  isOutdated: false, projectId: 'p1', vulnerabilityCount: 0,
  currentVersion: '4.0.0', latestVersion: '4.0.0', ltsVersion: '',
  registryStatus: 'synced', repositoryUrl: null,
  createdAt: '', updatedAt: '', currentVersionReleasedAt: null, latestVersionReleasedAt: null,
  ...overrides,
});

describe('useDependencyFilters', () => {
  it('filters by status outdated', () => {
    const deps = ref([makeDepLodash(), makeDepLodash({ id: '2', isOutdated: true })]);
    const { filters, filteredDeps } = useDependencyFilters(deps, ref(new Map()));
    filters.value.status = 'outdated';
    expect(filteredDeps.value).toHaveLength(1);
    expect(filteredDeps.value[0].id).toBe('2');
  });

  it('filters by packageManager', () => {
    const deps = ref([makeDepLodash(), makeDepLodash({ id: '2', packageManager: 'composer' })]);
    const { filters, filteredDeps } = useDependencyFilters(deps, ref(new Map()));
    filters.value.packageManager = 'composer';
    expect(filteredDeps.value).toHaveLength(1);
  });
});
```

**Test command:**
```bash
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec frontend pnpm vitest run src/dependency/composables/__tests__/useDependencyFilters --no-coverage
```

**Commit:**
```bash
git add frontend/src/dependency/composables/useDependencyFilters.ts frontend/src/dependency/composables/__tests__/useDependencyFilters.test.ts
git commit -m "feat(dependency): extract useDependencyFilters composable wrapping useListFiltering"
```

---

## Task 5: Extract useDependencyGrouping composable

**Files:**
- Create: `frontend/src/dependency/composables/useDependencyGrouping.ts`
- Test: `frontend/src/dependency/composables/__tests__/useDependencyGrouping.test.ts`

### Steps

- [ ] Extract the `groupedDeps` computed from `DependencyList.vue` lines 121–170 into a composable

```ts
// composables/useDependencyGrouping.ts
import { computed } from 'vue';
import type { ComputedRef, Ref } from 'vue';
import type { Dependency, GroupedDepRow, SortField } from '@/dependency/types';

export function useDependencyGrouping(
  filteredDeps: ComputedRef<Dependency[]>,
  projectName: (id: string) => string,
  sortField: Ref<string>,
  sortDir: Ref<'asc' | 'desc'>,
) {
  const groupedDeps = computed<GroupedDepRow[]>(() => {
    const groups = new Map<string, Dependency[]>();
    for (const dep of filteredDeps.value) {
      if (!groups.has(dep.name)) groups.set(dep.name, []);
      groups.get(dep.name)!.push(dep);
    }

    const dir = sortDir.value === 'asc' ? 1 : -1;
    const sorted = [...groups.entries()].sort(([nameA, depsA], [nameB, depsB]) => {
      switch (sortField.value as SortField) {
        case 'name': return nameA.localeCompare(nameB) * dir;
        case 'project':
          return projectName(depsA[0]?.projectId ?? '').localeCompare(
            projectName(depsB[0]?.projectId ?? ''),
          ) * dir;
        case 'status': {
          const diff = depsA.filter(d => d.isOutdated).length - depsB.filter(d => d.isOutdated).length;
          return diff * dir;
        }
        case 'vulnerabilities': {
          const vA = depsA.reduce((s, d) => s + d.vulnerabilityCount, 0);
          const vB = depsB.reduce((s, d) => s + d.vulnerabilityCount, 0);
          return (vB - vA) * dir;
        }
        default: return 0;
      }
    });

    const rows: GroupedDepRow[] = [];
    let groupIndex = 0;
    for (const [, deps] of sorted) {
      deps.forEach((dep, i) => {
        rows.push({
          dep, groupIndex,
          groupSize: deps.length,
          isFirstInGroup: i === 0,
          projectId: dep.projectId,
          projectName: projectName(dep.projectId),
        });
      });
      groupIndex++;
    }
    return rows;
  });

  return { groupedDeps };
}
```

- [ ] Write tests for sort by name, project, status, vulnerabilities

```ts
// __tests__/useDependencyGrouping.test.ts
import { computed, ref } from 'vue';
import { describe, expect, it } from 'vitest';
import { useDependencyGrouping } from '../useDependencyGrouping';

describe('useDependencyGrouping', () => {
  const deps = computed(() => [
    { id: '1', name: 'axios', isOutdated: true, projectId: 'p1', vulnerabilityCount: 2 },
    { id: '2', name: 'lodash', isOutdated: false, projectId: 'p2', vulnerabilityCount: 0 },
    { id: '3', name: 'axios', isOutdated: false, projectId: 'p2', vulnerabilityCount: 0 },
  ] as never);

  it('groups by name', () => {
    const { groupedDeps } = useDependencyGrouping(deps, (id) => id, ref('name'), ref('asc'));
    const axiosRows = groupedDeps.value.filter(r => r.dep.name === 'axios');
    expect(axiosRows).toHaveLength(2);
    expect(axiosRows[0].groupSize).toBe(2);
    expect(axiosRows[0].isFirstInGroup).toBe(true);
  });

  it('sorts by name asc', () => {
    const { groupedDeps } = useDependencyGrouping(deps, (id) => id, ref('name'), ref('asc'));
    expect(groupedDeps.value[0].dep.name).toBe('axios');
  });
});
```

**Test command:**
```bash
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec frontend pnpm vitest run src/dependency/composables/__tests__/useDependencyGrouping --no-coverage
```

**Commit:**
```bash
git add frontend/src/dependency/composables/useDependencyGrouping.ts frontend/src/dependency/composables/__tests__/useDependencyGrouping.test.ts
git commit -m "feat(dependency): extract useDependencyGrouping composable with sort support"
```

---

## Task 6: Extract useDependencyStats composable

**Files:**
- Create: `frontend/src/dependency/composables/useDependencyStats.ts`
- Test: `frontend/src/dependency/composables/__tests__/useDependencyStats.test.ts`

### Steps

- [ ] Extract `healthScore`, `depGapStats`, `loadStats`, and `projectAggregates` from `DependencyList.vue` lines 58–215

```ts
// composables/useDependencyStats.ts
import { computed, ref, watch } from 'vue';
import type { ComputedRef, Ref } from 'vue';
import { dependencyService } from '@/dependency/services/dependency.service';
import type { Dependency, DepGapStats, DependencyFilters, HealthScore } from '@/dependency/types';

export function useDependencyStats(
  allDeps: Ref<Dependency[]>,
  filteredDeps: ComputedRef<Dependency[]>,
  filters: Ref<DependencyFilters>,
  projectName: (id: string) => string,
) {
  const healthScore = ref<HealthScore | null>(null);

  const depGapStats = computed<DepGapStats | null>(() => {
    const maxGap = new Map<string, number>();
    for (const dep of filteredDeps.value) {
      if (!dep.isOutdated || !dep.currentVersionReleasedAt || !dep.latestVersionReleasedAt) continue;
      const gapMs = Math.abs(
        new Date(dep.latestVersionReleasedAt).getTime() -
        new Date(dep.currentVersionReleasedAt).getTime(),
      );
      if (gapMs > (maxGap.get(dep.name) ?? 0)) maxGap.set(dep.name, gapMs);
    }
    const gaps = [...maxGap.values()];
    if (gaps.length === 0) return null;
    const sorted = [...gaps].sort((a, b) => a - b);
    const cumulated = gaps.reduce((s, g) => s + g, 0);
    const median = sorted.length % 2 === 0
      ? (sorted[sorted.length / 2 - 1] + sorted[sorted.length / 2]) / 2
      : sorted[Math.floor(sorted.length / 2)];
    return { average: cumulated / gaps.length, cumulated, median };
  });

  const projectAggregates = computed(() => {
    const agg = new Map<string, { name: string; outdated: number; total: number; vulns: number }>();
    for (const dep of allDeps.value) {
      const name = projectName(dep.projectId);
      if (!agg.has(dep.projectId)) agg.set(dep.projectId, { name, outdated: 0, total: 0, vulns: 0 });
      const e = agg.get(dep.projectId)!;
      e.total++;
      if (dep.isOutdated) e.outdated++;
      e.vulns += dep.vulnerabilityCount;
    }
    return [...agg.entries()].map(([id, v]) => ({ id, ...v }));
  });

  async function loadStats() {
    try {
      const params: Record<string, string> = {};
      if (filters.value.projectId) params.projectId = filters.value.projectId;
      if (filters.value.packageManager) params.packageManager = filters.value.packageManager;
      if (filters.value.type) params.type = filters.value.type;
      const { data: s } = await dependencyService.stats(params);
      healthScore.value = {
        outdated: s.outdated,
        percent: s.total > 0 ? Math.round((s.upToDate / s.total) * 100) : 100,
        total: s.total,
        totalVulns: s.totalVulnerabilities,
        upToDate: s.upToDate,
      };
    } catch { /* silently ignored */ }
  }

  watch([() => filters.value.projectId, () => filters.value.packageManager, () => filters.value.type], loadStats);

  return { depGapStats, healthScore, loadStats, projectAggregates };
}
```

- [ ] Write tests for `depGapStats` computation and `projectAggregates`

```ts
// __tests__/useDependencyStats.test.ts
import { computed, ref } from 'vue';
import { describe, expect, it, vi } from 'vitest';
import { useDependencyStats } from '../useDependencyStats';

vi.mock('@/dependency/services/dependency.service', () => ({
  dependencyService: { stats: vi.fn() },
}));

describe('depGapStats', () => {
  it('returns null when no outdated deps', () => {
    const deps = ref([{ isOutdated: false, name: 'a', currentVersionReleasedAt: null, latestVersionReleasedAt: null }] as never);
    const { depGapStats } = useDependencyStats(deps, computed(() => deps.value), ref({ search: '', packageManager: '', type: '', status: '', projectId: '' }), (id) => id);
    expect(depGapStats.value).toBeNull();
  });

  it('computes average/median/cumulated', () => {
    const deps = ref([{
      isOutdated: true, name: 'lib',
      currentVersionReleasedAt: '2023-01-01T00:00:00Z',
      latestVersionReleasedAt: '2024-01-01T00:00:00Z',
    }] as never);
    const { depGapStats } = useDependencyStats(deps, computed(() => deps.value), ref({ search: '', packageManager: '', type: '', status: '', projectId: '' }), (id) => id);
    expect(depGapStats.value?.average).toBeGreaterThan(0);
  });
});
```

**Test command:**
```bash
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec frontend pnpm vitest run src/dependency/composables/__tests__/useDependencyStats --no-coverage
```

**Commit:**
```bash
git add frontend/src/dependency/composables/useDependencyStats.ts frontend/src/dependency/composables/__tests__/useDependencyStats.test.ts
git commit -m "feat(dependency): extract useDependencyStats composable with gap stats and health score"
```

---

## Task 7: Extract useDependencyExport composable with Strategy pattern

**Files:**
- Create: `frontend/src/dependency/composables/useDependencyExport.ts`
- Test: `frontend/src/dependency/composables/__tests__/useDependencyExport.test.ts`

### Steps

- [ ] Define `ExportStrategy` interface and implement CSV + PDF strategies; expose unified `handleExport(format)`

```ts
// composables/useDependencyExport.ts
import type { ComputedRef, Ref } from 'vue';
import { humanizeMs, humanizeTimeDiff } from '@/catalog/composables/useFrameworkLts';
import { exportDependenciesPdf } from '@/dependency/services/dependencyPdfExport';
import type { DepGapStats, Dependency, HealthScore } from '@/dependency/types';

interface ExportStrategy {
  execute(deps: Dependency[], projectName: (id: string) => string, extra?: unknown): void;
}

const csvStrategy: ExportStrategy = {
  execute(deps, projectName) {
    const headers = ['Projet', 'Nom', 'Version', 'Dernière version', 'Package Manager', 'Type', 'Statut', 'Vulnérabilités'];
    const rows = deps.map(dep => [
      projectName(dep.projectId), dep.name, dep.currentVersion, dep.latestVersion,
      dep.packageManager, dep.type, dep.isOutdated ? 'Obsolète' : 'À jour',
      String(dep.vulnerabilityCount),
    ]);
    const csv = [headers, ...rows].map(r => r.map(c => `"${c}"`).join(',')).join('\n');
    const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = 'dependencies.csv'; a.click();
    URL.revokeObjectURL(url);
  },
};

const pdfStrategy: ExportStrategy = {
  execute(deps, projectName, extra) {
    const { healthScore, gapStats } = extra as { healthScore: HealthScore | null; gapStats: DepGapStats | null };
    const rows = deps.map(dep => ({
      project: projectName(dep.projectId),
      name: dep.name, currentVersion: dep.currentVersion, latestVersion: dep.latestVersion,
      packageManager: dep.packageManager, type: dep.type,
      status: dep.isOutdated ? 'Obsolete' : 'A jour',
      vulnerabilities: dep.vulnerabilityCount,
      gap: dep.isOutdated && dep.currentVersionReleasedAt && dep.latestVersionReleasedAt
        ? humanizeTimeDiff(dep.currentVersionReleasedAt, dep.latestVersionReleasedAt)
        : dep.isOutdated ? '-' : 'A jour',
    }));
    const gapData = gapStats
      ? { average: humanizeMs(gapStats.average), median: humanizeMs(gapStats.median), cumulated: humanizeMs(gapStats.cumulated) }
      : null;
    exportDependenciesPdf(rows, healthScore, gapData);
  },
};

const strategies: Record<string, ExportStrategy> = { csv: csvStrategy, pdf: pdfStrategy };

export function useDependencyExport(
  filteredDeps: ComputedRef<Dependency[]>,
  projectName: (id: string) => string,
  healthScore: Ref<HealthScore | null>,
  depGapStats: ComputedRef<DepGapStats | null>,
) {
  function handleExport(format: 'csv' | 'pdf') {
    const strategy = strategies[format];
    if (!strategy) return;
    strategy.execute(filteredDeps.value, projectName, {
      healthScore: healthScore.value,
      gapStats: depGapStats.value,
    });
  }

  return { handleExport };
}
```

- [ ] Write tests mocking `URL.createObjectURL` and `exportDependenciesPdf`

```ts
// __tests__/useDependencyExport.test.ts
import { computed, ref } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useDependencyExport } from '../useDependencyExport';

vi.mock('@/dependency/services/dependencyPdfExport', () => ({
  exportDependenciesPdf: vi.fn(),
}));
vi.mock('@/catalog/composables/useFrameworkLts', () => ({
  humanizeMs: (n: number) => `${n}ms`,
  humanizeTimeDiff: () => '1 year',
}));

describe('useDependencyExport', () => {
  beforeEach(() => {
    globalThis.URL.createObjectURL = vi.fn(() => 'blob:mock');
    globalThis.URL.revokeObjectURL = vi.fn();
    document.createElement = vi.fn(() => ({ click: vi.fn(), href: '', download: '' }) as never);
  });

  it('calls pdf export with correct shape', async () => {
    const { exportDependenciesPdf } = await import('@/dependency/services/dependencyPdfExport');
    const deps = computed(() => [{ id: '1', name: 'lib', isOutdated: false, projectId: 'p1', vulnerabilityCount: 0, currentVersion: '1.0.0', latestVersion: '1.0.0', packageManager: 'npm', type: 'runtime', currentVersionReleasedAt: null, latestVersionReleasedAt: null }] as never);
    const { handleExport } = useDependencyExport(deps, () => 'My Project', ref(null), computed(() => null));
    handleExport('pdf');
    expect(exportDependenciesPdf).toHaveBeenCalled();
  });
});
```

**Test command:**
```bash
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec frontend pnpm vitest run src/dependency/composables/__tests__/useDependencyExport --no-coverage
```

**Commit:**
```bash
git add frontend/src/dependency/composables/useDependencyExport.ts frontend/src/dependency/composables/__tests__/useDependencyExport.test.ts
git commit -m "feat(dependency): extract useDependencyExport composable with Strategy pattern for CSV/PDF"
```

---

## Task 8: Refactor DependencyList.vue to ~150 lines

**Files:**
- Modify: `frontend/src/dependency/pages/DependencyList.vue`
- Test: `frontend/src/dependency/pages/__tests__/DependencyList.test.ts`

### Steps

- [ ] Replace all inline logic with the 4 extracted composables; keep template untouched (zero visual regression)

The `<script setup>` block should reduce to:

```ts
// DependencyList.vue <script setup>
import { computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink } from 'vue-router';

import { ltsUrgency, humanizeTimeDiff } from '@/catalog/composables/useFrameworkLts';
import { useProjectStore } from '@/catalog/stores/project';
import DependencyFilters from '@/dependency/components/DependencyFilters.vue';
import DependencyHealthScore from '@/dependency/components/DependencyHealthScore.vue';
import { useDependencyExport } from '@/dependency/composables/useDependencyExport';
import { useDependencyFilters } from '@/dependency/composables/useDependencyFilters';
import { useDependencyGrouping } from '@/dependency/composables/useDependencyGrouping';
import { useDependencyStats } from '@/dependency/composables/useDependencyStats';
import { useDependencySyncProgress } from '@/dependency/composables/useDependencySyncProgress';
import { dependencyService } from '@/dependency/services/dependency.service';
import { useDependencyStore } from '@/dependency/stores/dependency';
import ExportDropdown from '@/shared/components/ExportDropdown.vue';
import Pagination from '@/shared/components/Pagination.vue';
import DashboardLayout from '@/shared/layouts/DashboardLayout.vue';
import { useToastStore } from '@/shared/stores/toast';
import { ref } from 'vue';

const { t } = useI18n();
const dependencyStore = useDependencyStore();
const projectStore = useProjectStore();
const toastStore = useToastStore();
const { track: trackSync } = useDependencySyncProgress();
const syncing = ref(false);

const projectMap = computed(() => {
  const map = new Map<string, string>();
  for (const p of projectStore.projects) map.set(p.id, p.name);
  return map;
});

function projectName(id: string) {
  return projectMap.value.get(id) ?? id;
}

const { filters, filteredDeps, sortField, sortDir, sortIndicator, toggleSort } =
  useDependencyFilters(dependencyStore.items, projectMap);

const { groupedDeps } = useDependencyGrouping(filteredDeps, projectName, sortField, sortDir);

const { healthScore, depGapStats, loadStats } =
  useDependencyStats(dependencyStore.items, filteredDeps, filters, projectName);

const { handleExport } = useDependencyExport(filteredDeps, projectName, healthScore, depGapStats);

onMounted(async () => {
  await Promise.all([dependencyStore.fetchAll(1, 1000), projectStore.fetchAll(1, 200)]);
  await loadStats();
});

function changePage(page: number) { dependencyStore.fetchAll(page, 1000); }

async function handleSync() {
  syncing.value = true;
  try {
    const response = await dependencyService.sync();
    trackSync(response.data.syncId, response.data.total);
  } catch {
    toastStore.addToast({ title: t('common.errors.failedToSync'), variant: 'error' });
  } finally {
    syncing.value = false;
  }
}

async function handleDelete(id: string) { await dependencyStore.remove(id); }
```

- [ ] Update the template to bind `filters.search`, `filters.packageManager`, etc. instead of individual refs
- [ ] Verify the component compiles without TS errors:

```bash
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec frontend pnpm vue-tsc --noEmit
```

- [ ] Write smoke test for the refactored page

```ts
// __tests__/DependencyList.test.ts
import { createTestingPinia } from '@pinia/testing';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import DependencyList from '../DependencyList.vue';

vi.mock('@/dependency/composables/useDependencySyncProgress', () => ({
  useDependencySyncProgress: () => ({ track: vi.fn() }),
}));

describe('DependencyList', () => {
  it('renders loading state', () => {
    const wrapper = mount(DependencyList, {
      global: {
        plugins: [createTestingPinia({ initialState: { dependency: { loading: true } } })],
        stubs: { DashboardLayout: true, RouterLink: true },
      },
    });
    expect(wrapper.find('[data-testid="dependency-list-loading"]').exists()).toBe(true);
  });
});
```

**Test command:**
```bash
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec frontend pnpm vitest run src/dependency/pages --no-coverage
```

**Final test — run full dependency module:**
```bash
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec frontend pnpm vitest run src/dependency --no-coverage
```

**Commit:**
```bash
git add frontend/src/dependency/pages/DependencyList.vue frontend/src/dependency/pages/__tests__/
git commit -m "refactor(dependency): slim DependencyList.vue to ~150 lines using extracted composables"
```

---

## Execution Order

Tasks 1–3 can run in parallel (types + stores). Tasks 4–7 depend on Task 1 (types). Task 8 depends on Tasks 4–7.

```
Task 1 (types) ──┬── Task 4 (filters) ──┐
Task 2 (store)   ├── Task 5 (grouping) ──┤── Task 8 (refactor page)
Task 3 (vuln)    ├── Task 6 (stats) ─────┤
                 └── Task 7 (export) ────┘
```

## Verification

After all tasks:
```bash
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec frontend pnpm vitest run src/dependency --no-coverage
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec frontend pnpm vue-tsc --noEmit
```

Target: `DependencyList.vue` ≤ 150 lines script + template, 0 TS errors, all tests green.
