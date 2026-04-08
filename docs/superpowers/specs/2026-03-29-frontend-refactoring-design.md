# Frontend Refactoring — Design Spec

**Date:** 2026-03-29
**Scope:** Full frontend refactoring — abstractions, test coverage, mutation testing, CI hardening, E2E
**Approach:** Bottom-up systématique (shared foundations → module-by-module migration)

---

## Context

Monark frontend is a Vue 3.5 + TypeScript strict + Pinia + Vite 6 + Tailwind 4 application organized in DDD bounded contexts (identity, catalog, dependency, activity) with a shared cross-cutting module. ESLint boundaries enforce module isolation.

### Current State

- **Code quality:** 8.2/10 — consistent patterns, near-perfect type safety (1 `any`), 100% Composition API
- **Duplication:** 13 CRUD services (~390 lines) and 15 CRUD stores (~900 lines) follow identical patterns
- **Test coverage:** 56 test files, but 36 components/pages untested (~35% UI uncovered)
- **CI gates:** No frontend coverage threshold (backend = 80% min), Stryker configured but not in CI
- **E2E:** None (no Playwright/Cypress)
- **Test utilities:** No frontend factories or shared helpers (backend has factories)

### Goals

- Reduce CRUD boilerplate by ~65% via functional factory patterns
- Achieve ≥ 80% code coverage with CI gate
- Achieve ≥ 80% mutation score with CI gate (front + back)
- E2E coverage on all critical user flows
- Project as a technological showcase

---

## Phase 1 — Test Infrastructure

### Test Factories

One factory per domain entity in `tests/factories/`:

```
tests/factories/
├── project.factory.ts
├── provider.factory.ts
├── tech-stack.factory.ts
├── merge-request.factory.ts
├── user.factory.ts
├── access-token.factory.ts
├── dependency.factory.ts
├── vulnerability.factory.ts
├── activity-event.factory.ts
├── notification.factory.ts
├── sync-task.factory.ts
└── build-metric.factory.ts
```

Each factory exports a `createX(overrides?: Partial<X>): X` function that returns a fully valid entity with sensible defaults. Overrides allow customizing any field.

```ts
// Example: tests/factories/project.factory.ts
import type { Project } from '@/catalog/types/project';
import { ProjectVisibility } from '@/shared/types/enums';

export function createProject(overrides?: Partial<Project>): Project {
  return {
    id: 'project-1',
    name: 'My Project',
    slug: 'my-project',
    description: null,
    visibility: ProjectVisibility.Public,
    techStacks: [],
    ...overrides,
  };
}
```

### Test Helpers

```
tests/helpers/
├── mount.ts       # mountWithPlugins() — auto-injects i18n, router stubs, pinia
├── api-mock.ts    # createApiMock() — typed fetch mock returning ApiResponse<T>
└── store.ts       # createTestStore() — pinia with pre-set initial state
```

**`mountWithPlugins(component, options?)`**
- Creates a fresh Pinia instance
- Stubs RouterLink and RouterView
- Provides i18n plugin
- Accepts additional mount options (props, slots, stubs, global mocks)
- Eliminates repetitive plugin setup in every component test

**`createApiMock(responses)`**
- Mocks `globalThis.fetch` with typed responses
- Maps URL patterns to `ApiResponse<T>` payloads
- Supports error responses (4xx, 5xx)
- Auto-resets between tests

**`createTestStore(storeFactory, initialState)`**
- Initializes a Pinia store with predefined state
- Useful for component tests that need a store in a specific state
- Avoids coupling component tests to API calls

### Setup File Enhancement

Enrich existing `tests/setup.ts`:
- Import and auto-clear `createApiMock` between tests
- Reset localStorage
- Set i18n locale to 'en' (already present)

---

## Phase 2 — Shared Abstractions

### Service Factory

```ts
// shared/services/createCrudService.ts
import { api } from '@/shared/utils/api';
import type { ApiResponse } from '@/shared/types';
import type { CrudService, PaginatedResponse } from '@/shared/types/crud';

export function createCrudService<T, TCreate = Partial<T>, TUpdate = Partial<T>>(
  basePath: string,
): CrudService<T, TCreate, TUpdate> {
  return {
    list: (page = 1, perPage = 30) =>
      api.get<ApiResponse<PaginatedResponse<T>>>(`${basePath}?page=${page}&per_page=${perPage}`),
    get: (id: string) =>
      api.get<ApiResponse<T>>(`${basePath}/${id}`),
    create: (data: TCreate) =>
      api.post<ApiResponse<T>>(basePath, data),
    update: (id: string, data: TUpdate) =>
      api.put<ApiResponse<T>>(`${basePath}/${id}`, data),
    remove: (id: string) =>
      api.delete<void>(`${basePath}/${id}`),
  };
}
```

Services become a one-liner spread + domain-specific methods:

```ts
// catalog/services/project.service.ts
export const projectService = {
  ...createCrudService<Project, CreateProject, UpdateProject>('/catalog/projects'),
  scan: (id: string) => api.post<ApiResponse<void>>(`/catalog/projects/${id}/scan`, {}),
  sync: (id: string) => api.post<ApiResponse<void>>(`/catalog/projects/${id}/sync`, {}),
};
```

### Store Factory

```ts
// shared/stores/createCrudStore.ts
import { ref } from 'vue';
import { defineStore } from 'pinia';
import type { CrudService, PaginationMeta } from '@/shared/types/crud';

export function createCrudStore<T, TCreate = Partial<T>, TUpdate = Partial<T>>(
  name: string,
  service: CrudService<T, TCreate, TUpdate>,
) {
  return defineStore(name, () => {
    const items = ref<T[]>([]);
    const current = ref<T | null>(null);
    const loading = ref(false);
    const error = ref<string | null>(null);
    const pagination = ref<PaginationMeta>({ page: 1, totalPages: 1 });

    async function fetchAll(page = 1, perPage = 30): Promise<void> {
      loading.value = true;
      error.value = null;
      try {
        const response = await service.list(page, perPage);
        items.value = response.data.items;
        pagination.value = { page: response.data.page, totalPages: response.data.totalPages };
      } catch (e) {
        error.value = e instanceof Error ? e.message : 'Failed to fetch items';
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
      } catch (e) {
        error.value = e instanceof Error ? e.message : 'Failed to fetch item';
      } finally {
        loading.value = false;
      }
    }

    async function create(data: TCreate): Promise<T> {
      loading.value = true;
      error.value = null;
      try {
        const response = await service.create(data);
        return response.data;
      } catch (e) {
        error.value = e instanceof Error ? e.message : 'Failed to create item';
        throw e;
      } finally {
        loading.value = false;
      }
    }

    async function update(id: string, data: TUpdate): Promise<T> {
      loading.value = true;
      error.value = null;
      try {
        const response = await service.update(id, data);
        return response.data;
      } catch (e) {
        error.value = e instanceof Error ? e.message : 'Failed to update item';
        throw e;
      } finally {
        loading.value = false;
      }
    }

    async function remove(id: string): Promise<void> {
      loading.value = true;
      error.value = null;
      try {
        await service.remove(id);
        items.value = (items.value as (T & { id: string })[]).filter((item) => item.id !== id) as T[];
      } catch (e) {
        error.value = e instanceof Error ? e.message : 'Failed to delete item';
        throw e;
      } finally {
        loading.value = false;
      }
    }

    return { items, current, loading, error, pagination, fetchAll, fetchOne, create, update, remove };
  });
}
```

Stores with domain-specific logic compose on top:

```ts
// catalog/stores/project.ts
export const useProjectStore = defineStore('project', () => {
  const crud = createCrudStore<Project>('project-crud', projectService)();

  async function scanProject(id: string): Promise<void> { /* ... */ }
  async function syncAll(): Promise<void> { /* ... */ }

  return { ...crud, scanProject, syncAll };
});
```

### Shared Types

```ts
// shared/types/crud.ts
export interface CrudService<T, TCreate = Partial<T>, TUpdate = Partial<T>> {
  list(page?: number, perPage?: number): Promise<ApiResponse<PaginatedResponse<T>>>;
  get(id: string): Promise<ApiResponse<T>>;
  create(data: TCreate): Promise<ApiResponse<T>>;
  update(id: string, data: TUpdate): Promise<ApiResponse<T>>;
  remove(id: string): Promise<void>;
}

export interface PaginatedResponse<T> {
  items: T[];
  page: number;
  totalPages: number;
  totalItems: number;
}

export interface PaginationMeta {
  page: number;
  totalPages: number;
}
```

### Impact

| Metric | Before | After |
|--------|--------|-------|
| Service CRUD boilerplate | ~390 lines across 13 files | ~150 lines (factory + extensions) |
| Store CRUD boilerplate | ~900 lines across 15 files | ~300 lines (factory + extensions) |
| **Total reduction** | **~1290 lines** | **~450 lines (~65% reduction)** |

---

## Phase 3 — Module Migration: Catalog (Pilot)

### Step 1: Write missing tests (before refactoring)

**Pages (2):**
- TechStackForm.vue
- MergeRequestList.vue

**Components (10):**
- ProviderInfoCard, RemoteProjectsSection, ProjectDependenciesTab
- ProviderIcon, TechStackFilters, ProviderCard
- ProjectTechStacksTab, ProjectMergeRequestsTab, TechStackTable, ProviderStatsCards

**Services (1):**
- techStackPdfExport.ts

### Step 2: Migrate services to `createCrudService`

- `project.service.ts` → factory + scan/sync/import extensions
- `provider.service.ts` → factory + testConnection extension
- `tech-stack.service.ts` → factory only
- `merge-request.service.ts` → factory only

### Step 3: Migrate stores to `createCrudStore`

- `project.ts` → factory + stats/scan/syncAll
- `provider.ts` → factory + testConnection
- `tech-stack.ts` → factory only
- `merge-request.ts` → factory only

### Step 4: Validate

- All existing tests pass
- All new tests pass
- Coverage ≥ 80% for catalog module
- No regressions

---

## Phase 4 — Module Migration: Identity

### Missing tests

**Pages (4):**
- AccessTokenForm.vue
- AccessTokenList.vue
- ProfilePage.vue
- UserDetail.vue

### Service migration

- `auth.service.ts` → custom (login/register/logout/me don't fit CRUD pattern)
- `user.service.ts` → factory + custom methods
- `access-token.service.ts` → factory only

### Store migration

- `auth.ts` → custom (auth state management, token handling)
- `user.ts` → factory only
- `access-token.ts` → factory only

---

## Phase 5 — Module Migration: Dependency

### Missing tests

**Pages (2):**
- DependencyForm.vue
- VulnerabilityForm.vue

**Components (2):**
- DependencyFilters.vue
- DependencyHealthScore.vue

**Services (1):**
- dependencyPdfExport.ts

### Service migration

- `dependency.service.ts` → factory + custom methods
- `vulnerability.service.ts` → factory only

### Store migration

- `dependency.ts` → factory + sync/stats
- `vulnerability.ts` → factory only

---

## Phase 6 — Module Migration: Activity

### Missing tests

**Pages (6):**
- SyncTaskList.vue
- NotificationList.vue
- NotificationDetail.vue
- MessengerMonitor.vue
- ActivityEventList.vue
- ActivityEventDetail.vue

**Stores (1):**
- messenger.ts (missing test)

### Service migration

- `dashboard.service.ts` → custom (aggregation, not CRUD)
- `activity-event.service.ts` → factory only
- `notification.service.ts` → factory + markRead extension
- `sync-task.service.ts` → factory only
- `messenger.service.ts` → custom (SSE monitoring)

### Store migration

- `dashboard.ts` → custom (aggregated stats)
- `activity-event.ts` → factory only
- `notification.ts` → factory + markRead
- `sync-task.ts` → factory only
- `messenger.ts` → custom (Mercure SSE state)

---

## Phase 7 — Shared Remaining

### Missing tests

- `shared/layouts/AuthLayout.vue`
- `shared/layouts/DashboardLayout.vue`
- `shared/utils/pdfExport.ts`
- `app/router.ts` — navigation guards
- Route definitions for each module (`identity/routes.ts`, `catalog/routes.ts`, `dependency/routes.ts`, `activity/routes.ts`)

---

## Phase 8 — CI Hardening

### Frontend CI Changes

**Coverage gate** — modify vitest command:
```yaml
pnpm vitest run --coverage --coverage.thresholds.lines=80 --coverage.thresholds.branches=80 --coverage.thresholds.functions=80 --coverage.reporter=text --coverage.reporter=json-summary
```

**Mutation testing** — new CI step:
```yaml
- name: Mutation testing (frontend)
  run: pnpm stryker run
```

**Stryker config update** (`stryker.config.mjs`):
```js
thresholds: { break: 80, high: 80, low: 60 }  // break: null → 80
```

### Backend CI Changes

**Mutation testing** — new CI step (if not present):
```yaml
- name: Mutation testing (backend)
  run: php vendor/bin/infection --min-msi=80 --min-covered-msi=80
```

### Final CI Gates Summary

| Gate | Frontend | Backend |
|------|----------|---------|
| Lint | ESLint | PHP-CS-Fixer |
| Format | Prettier | PHP-CS-Fixer |
| Types | vue-tsc | PHPStan |
| Architecture | eslint-boundaries | Deptrac |
| Coverage ≥ 80% | vitest --coverage.thresholds | pest --min=80 |
| Mutation ≥ 80% | Stryker --break 80 | Infection --min-msi=80 |
| Security audit | pnpm audit | composer audit |
| E2E | Playwright | — |

---

## Phase 9 — E2E with Playwright

### Setup

- Framework: Playwright
- Directory: `frontend/e2e/`
- Config: `frontend/playwright.config.ts`
- Pattern: Page Object Model (leveraging existing `data-testid` attributes)
- Auth: Shared `storageState` fixture to avoid re-login per test
- Backend: Real backend via Docker Compose (no API mocking)

### Structure

```
frontend/e2e/
├── fixtures/
│   └── auth.fixture.ts           # Authenticated session storageState
├── pages/
│   ├── login.page.ts
│   ├── dashboard.page.ts
│   ├── project-list.page.ts
│   └── provider-list.page.ts
├── specs/
│   ├── auth.spec.ts              # Login, register, logout, session expiry
│   ├── project-crud.spec.ts      # List, detail, tabs, delete
│   ├── provider-sync.spec.ts     # Add provider, test connection, import, sync progress
│   ├── dependency.spec.ts        # List, filter, vulnerabilities, health score
│   ├── navigation.spec.ts        # Sidebar, routing guards, 404
│   └── i18n.spec.ts              # Language switch, persistence, date formats
└── global-setup.ts               # Seed data, server health check
```

### Test Suites

| Suite | Priority | Scenarios |
|-------|----------|-----------|
| **auth** | P0 | Valid/invalid login, register, logout, unauthenticated redirect, 401 session expiry |
| **project-crud** | P0 | List projects, view detail, navigate tabs (tech stacks, deps, MRs), delete |
| **provider-sync** | P0 | Add provider, test connection, import projects, track sync progress |
| **dependency** | P1 | List dependencies, filter, view vulnerabilities, health score display |
| **navigation** | P1 | Sidebar active state, auth guards, responsive layout |
| **i18n** | P2 | Switch fr/en, localStorage persistence, date format changes |

### CI Job

```yaml
e2e:
  needs: [frontend, backend]
  services:
    postgres:
    backend:
    mercure:
  steps:
    - uses: actions/checkout@v4
    - uses: pnpm/action-setup@v4
    - run: pnpm install
    - run: pnpm playwright install --with-deps
    - run: pnpm playwright test
    - uses: actions/upload-artifact@v4
      if: failure()
      with:
        name: playwright-report
        path: frontend/e2e/playwright-report/
```

---

## Execution Order Summary

| Phase | Content | Validation |
|-------|---------|------------|
| 1 | Test factories (12) + helpers (3) + setup | Helpers pass their own unit tests |
| 2 | `createCrudService`, `createCrudStore`, `CrudService` types | Factory unit tests pass |
| 3 | Catalog: missing tests → migrate services/stores → cleanup | Coverage ≥ 80% catalog |
| 4 | Identity: missing tests → migrate → cleanup | Coverage ≥ 80% identity |
| 5 | Dependency: missing tests → migrate → cleanup | Coverage ≥ 80% dependency |
| 6 | Activity: missing tests → migrate → cleanup | Coverage ≥ 80% activity |
| 7 | Shared: layouts, pdfExport, router, routes tests | Coverage ≥ 80% shared |
| 8 | CI: coverage gate front, Stryker front, Infection back | CI pipeline green |
| 9 | Playwright: setup + 6 suites + CI job | All E2E suites pass |

## Expected Outcome

- ~65% reduction in CRUD boilerplate (1290 → 450 lines)
- ≥ 80% code coverage enforced in CI (frontend + backend)
- ≥ 80% mutation score enforced in CI (frontend + backend)
- E2E coverage on all critical user flows
- Consistent factory patterns for test data
- CI pipeline as a quality showcase
