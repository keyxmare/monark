# Frontend Tests & Coverage Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Raise frontend test coverage from 14.7% to 70%+ and mutation score from 24% to 60%+.

**Architecture:** Test bottom-up: services first (thin API wrappers, easy to mock), then untested composables, then shared components, then key page flows. Each task is independent and produces passing tests.

**Tech Stack:** Vitest, @vue/test-utils, pnpm, Docker

**Commands:**
- Run all tests: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend sh -c 'pnpm vitest run'`
- Run single test: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend sh -c 'pnpm vitest run tests/unit/path/test.ts --reporter=verbose'`
- Run lint: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend sh -c 'pnpm lint'`
- Run coverage: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend sh -c 'pnpm vitest run --coverage'`

**Working directory:** `/Users/keyxmare/Projects/github.com/keyxmare/monark/frontend`

**Test patterns:**
- Services: mock `api` from `@/shared/utils/api` with `vi.mock`, verify correct endpoint/method/payload
- Composables: test returned refs/functions directly
- Components: mount with `@vue/test-utils`, mock stores via `vi.mock`, test rendering + interactions
- All tests use `describe/it/expect` from `vitest`

---

## Task 1: Test all Identity services (auth, user, access-token)

**Files:**
- Create: `tests/unit/identity/services/auth.service.test.ts`
- Create: `tests/unit/identity/services/user.service.test.ts`
- Create: `tests/unit/identity/services/access-token.service.test.ts`

- [ ] **Step 1: Read source files**

Read `src/identity/services/auth.service.ts`, `user.service.ts`, `access-token.service.ts` to see every function signature and endpoint.

- [ ] **Step 2: Write tests for all 3 services**

Pattern for each: mock `@/shared/utils/api`, call each service function, verify `api.get/post/put/delete` was called with the correct endpoint and payload.

Example:
```typescript
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@/shared/utils/api', () => ({
  api: {
    delete: vi.fn(),
    get: vi.fn(),
    patch: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
  },
}));

import { api } from '@/shared/utils/api';
import { authService } from '@/identity/services/auth.service';

describe('authService', () => {
  beforeEach(() => { vi.clearAllMocks(); });

  it('login calls POST /auth/login', async () => {
    vi.mocked(api.post).mockResolvedValue({ data: { token: 't', user: {} }, status: 200 });
    await authService.login('a@b.com', 'pwd');
    expect(api.post).toHaveBeenCalledWith('/auth/login', { email: 'a@b.com', password: 'pwd' });
  });
  // ... register, logout, getCurrentUser
});
```

Repeat for user.service (getAll, getOne, create, update, delete) and access-token.service.

- [ ] **Step 3: Run tests, fix lint, commit**

```bash
git add tests/unit/identity/services/
git commit -m "test(identity): add service tests — auth, user, access-token"
```

---

## Task 2: Test all Catalog services (project, provider, tech-stack, merge-request)

**Files:**
- Create: `tests/unit/catalog/services/project.service.test.ts`
- Create: `tests/unit/catalog/services/provider.service.test.ts`
- Create: `tests/unit/catalog/services/tech-stack.service.test.ts`
- Create: `tests/unit/catalog/services/merge-request.service.test.ts`

- [ ] **Step 1: Read source files**

Read all 4 service files in `src/catalog/services/`.

- [ ] **Step 2: Write tests**

Same pattern as Task 1. Mock `api`, verify endpoints/methods/payloads for every function.
Provider service is the largest (105 lines) with testConnection, listRemoteProjects, importProjects — test each.

- [ ] **Step 3: Run tests, fix lint, commit**

```bash
git add tests/unit/catalog/services/
git commit -m "test(catalog): add service tests — project, provider, tech-stack, merge-request"
```

---

## Task 3: Test all Dependency + Activity services

**Files:**
- Create: `tests/unit/dependency/services/dependency.service.test.ts`
- Create: `tests/unit/dependency/services/vulnerability.service.test.ts`
- Create: `tests/unit/activity/services/activity-event.service.test.ts`
- Create: `tests/unit/activity/services/dashboard.service.test.ts`
- Create: `tests/unit/activity/services/notification.service.test.ts`
- Create: `tests/unit/activity/services/sync-task.service.test.ts`
- Create: `tests/unit/activity/services/messenger.service.test.ts`

- [ ] **Step 1: Read all 7 service files**

- [ ] **Step 2: Write tests for all 7 services**

Same mock-api pattern. Most are small (9-67 lines).

- [ ] **Step 3: Run tests, fix lint, commit**

```bash
git add tests/unit/dependency/services/ tests/unit/activity/services/
git commit -m "test(dependency,activity): add service tests"
```

---

## Task 4: Test untested composables (useConfirmDelete, useDependencySyncProgress)

**Files:**
- Create: `tests/unit/shared/composables/useConfirmDelete.test.ts`
- Create: `tests/unit/dependency/composables/useDependencySyncProgress.test.ts`

- [ ] **Step 1: Read source files**

Read `src/shared/composables/useConfirmDelete.ts` and `src/dependency/composables/useDependencySyncProgress.ts`.

- [ ] **Step 2: Write useConfirmDelete tests**

Test: requestDelete sets target, isOpen becomes true, cancel clears target, confirm calls deleteFn and clears target.

- [ ] **Step 3: Write useDependencySyncProgress tests**

Test: track() creates toast and subscribes to Mercure, progress updates, completion, timeout handling. Mock useMercure and useToastStore.

- [ ] **Step 4: Run tests, fix lint, commit**

```bash
git add tests/unit/shared/composables/useConfirmDelete.test.ts tests/unit/dependency/composables/useDependencySyncProgress.test.ts
git commit -m "test: add useConfirmDelete and useDependencySyncProgress tests"
```

---

## Task 5: Test shared components (AppSidebar, AppTopbar, Pagination, ConfirmDialog)

**Files:**
- Create: `tests/unit/shared/components/AppSidebar.test.ts`
- Create: `tests/unit/shared/components/AppTopbar.test.ts`
- Create: `tests/unit/shared/components/Pagination.test.ts`
- Create: `tests/unit/shared/components/ConfirmDialog.test.ts`

- [ ] **Step 1: Read each component source**

- [ ] **Step 2: Write tests for each component**

Pattern: mount with `@vue/test-utils`, mock any stores/composables used, verify rendering and interactions.

For each component test at minimum:
- Renders without errors
- Key elements are present (data-testid or semantic queries)
- User interactions work (click events emit correct events or update state)

- [ ] **Step 3: Run tests, fix lint, commit**

```bash
git add tests/unit/shared/components/
git commit -m "test(shared): add component tests — AppSidebar, AppTopbar, Pagination, ConfirmDialog"
```

---

## Task 6: Test shared components (DropdownMenu, ExportDropdown, AppToast, AppToastContainer)

**Files:**
- Create: `tests/unit/shared/components/DropdownMenu.test.ts`
- Create: `tests/unit/shared/components/ExportDropdown.test.ts`
- Create: `tests/unit/shared/components/AppToast.test.ts`
- Create: `tests/unit/shared/components/AppToastContainer.test.ts`

- [ ] **Step 1: Read each component source**

- [ ] **Step 2: Write tests**

Same pattern. DropdownMenu: test open/close toggle, slot rendering. ExportDropdown: test export options rendering and click events. AppToast: test message display, progress bar, dismiss. AppToastContainer: test renders toasts from store.

- [ ] **Step 3: Run tests, fix lint, commit**

```bash
git add tests/unit/shared/components/
git commit -m "test(shared): add component tests — DropdownMenu, ExportDropdown, AppToast, AppToastContainer"
```

---

## Task 7: Test key page components — Identity

**Files:**
- Create: `tests/unit/identity/pages/LoginPage.test.ts`
- Create: `tests/unit/identity/pages/RegisterPage.test.ts`
- Create: `tests/unit/identity/pages/UserList.test.ts`

- [ ] **Step 1: Read page components**

Read `src/identity/pages/LoginPage.vue`, `RegisterPage.vue`, `UserList.vue`.

- [ ] **Step 2: Write tests**

For each page: mount with mocked router + stores. Test:
- Renders form fields / list items
- Submit dispatches correct store action
- Error state displays error message
- Navigation after success

- [ ] **Step 3: Run tests, fix lint, commit**

```bash
git add tests/unit/identity/pages/
git commit -m "test(identity): add page component tests — Login, Register, UserList"
```

---

## Task 8: Test key page components — Catalog

**Files:**
- Create: `tests/unit/catalog/pages/ProjectList.test.ts`
- Create: `tests/unit/catalog/pages/ProviderList.test.ts`

- [ ] **Step 1: Read page components**

- [ ] **Step 2: Write tests**

Mount with mocked stores. Test: renders list, loading state, error state, pagination interaction, navigation to detail.

- [ ] **Step 3: Run tests, fix lint, commit**

```bash
git add tests/unit/catalog/pages/
git commit -m "test(catalog): add page component tests — ProjectList, ProviderList"
```

---

## Task 9: Test key page components — Dependency + Activity

**Files:**
- Create: `tests/unit/dependency/pages/DependencyList.test.ts`
- Create: `tests/unit/activity/pages/DashboardPage.test.ts`

- [ ] **Step 1: Read page components**

- [ ] **Step 2: Write tests**

DependencyList: renders table, filters work, loading/error states.
DashboardPage: renders metrics, loading state.

- [ ] **Step 3: Run tests, fix lint, commit**

```bash
git add tests/unit/dependency/pages/ tests/unit/activity/pages/
git commit -m "test(dependency,activity): add page component tests — DependencyList, Dashboard"
```

---

## Task 10: Coverage report + mutation hardening

- [ ] **Step 1: Run coverage report**

Run: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend sh -c 'pnpm vitest run --coverage'`
Check: >= 70% line coverage

- [ ] **Step 2: Run Stryker mutation testing**

Run: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend sh -c 'pnpm mutation'`
Check: >= 60% MSI

- [ ] **Step 3: Identify and strengthen weak tests**

Review surviving mutants, add assertions to kill them.

- [ ] **Step 4: Final commit**

```bash
git add -A
git commit -m "test(frontend): achieve 70%+ coverage and 60%+ MSI"
```
