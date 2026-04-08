# Sub-Project 2: Frontend Tests & Coverage

**Date**: 2026-03-29
**Status**: Approved
**Depends on**: Sub-project 1 (composables refacto)
**Blocks**: Sub-project 3 (polish)

## Context

After sub-project 1, the frontend will have clean, focused composables and smaller components. The test coverage is currently at 14.7% line coverage and 24.64% MSI. 28 pages have zero tests, 16 services are untested, and 6 shared components lack tests.

## Goals

- Line coverage: 14.7% → 70%+
- Mutation score (Stryker): 24.64% → 60%+
- Every composable, service, and shared component has dedicated tests
- Page components have key interaction tests

## Scope

### Priority 1: New Composables (from sub-project 1)
- `useForm` — test validation, touched tracking, submit, reset
- `useListFiltering` — test sort toggle, debounce, filter computation
- `useLocalStorage` — test get/set, JSON serialization, defaults
- `useTechStackGrouping` — test grouping logic

### Priority 2: Services (16 files, 0 direct tests)
- Test each service function: correct URL, method, payload, response parsing
- Mock the API wrapper (`shared/utils/api.ts`)
- Verify error propagation

### Priority 3: Shared Components (8 untested)
- AppSidebar, AppTopbar, ConfirmDialog, Pagination
- DropdownMenu, ExportDropdown, AppToast, AppToastContainer

### Priority 4: Page Components (28 untested)
- Test key user flows: render, filter, sort, create, edit, delete
- Mount with mocked stores
- Focus on interactions, not snapshot testing

### Priority 5: Mutation Hardening
- Run Stryker per module
- Strengthen tests where mutants survive
- Target: covered MSI 70%+

## Constraints

- Vitest + @vue/test-utils (already installed)
- No E2E tests (separate concern)
- Tests must run in < 30s total

## Success Criteria

- [ ] `pnpm test:coverage` reports >= 70% line coverage
- [ ] `pnpm mutation` reports >= 60% MSI
- [ ] Every composable has a test file
- [ ] Every service has a test file
- [ ] Every shared component has a test file
- [ ] At least 1 test per page component (key flow)
