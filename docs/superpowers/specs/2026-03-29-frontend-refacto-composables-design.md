# Sub-Project 1: Frontend Refacto — Composables & Component Splitting

**Date**: 2026-03-29
**Status**: Approved
**Depends on**: Nothing
**Blocks**: Sub-project 2 (tests), Sub-project 3 (polish)

## Context

The frontend has solid architectural foundations (bounded contexts, strict TypeScript, consistent patterns) but suffers from duplicated logic and oversized components. 3 pages exceed 500 lines, form/list patterns are copy-pasted across contexts, and localStorage access is scattered.

This sub-project extracts reusable composables and splits large components to create a clean foundation for testing and polishing.

## Goals

- Extract duplicated patterns into shared composables
- Split the 3 largest components into focused sub-components
- Centralize localStorage access
- Zero regressions: all existing tests stay green, lint stays clean

## Deliverables

### 1. Composable: `useForm<T>`

**Location**: `src/shared/composables/useForm.ts`

Extract the repeated form pattern from ProviderForm, DependencyForm, VulnerabilityForm, TechStackForm, AccessTokenForm:
- Reactive form state from initial values
- Touched field tracking
- Validation errors computed from touched + empty required fields
- `isValid` computed
- `handleSubmit(callback)` that touches all fields then calls callback if valid
- `reset()` to restore initial values
- Generic `<T extends Record<string, unknown>>`

**Consumers**: ProviderForm, DependencyForm, VulnerabilityForm, TechStackForm, AccessTokenForm, RegisterPage, LoginPage

### 2. Composable: `useListFiltering<T>`

**Location**: `src/shared/composables/useListFiltering.ts`

Extract the repeated list filtering/sorting pattern from all list pages:
- `sortField` ref with default
- `sortDir` ref ('asc' | 'desc')
- `toggleSort(field)` function
- `sortIndicator(field)` function returning unicode arrow
- `search` ref with debounce (300ms)
- Generic filtered/sorted computed from items array + filter config

**Consumers**: DependencyList, VulnerabilityList, ProjectList, MergeRequestList, TechStackList, SyncTaskList, ActivityEventList, NotificationList, UserList, AccessTokenList

### 3. Composable: `useLocalStorage<T>`

**Location**: `src/shared/composables/useLocalStorage.ts`

Centralize the 3+ scattered localStorage accesses:
- Reactive ref synced with localStorage key
- Type-safe get/set with JSON serialization
- SSR-safe (no window access during SSR)
- Optional default value

**Replace**: Direct `localStorage.getItem/setItem` calls in auth store, useLocale, useSidebar, router guard

### 4. Split TechStackList.vue (972 → ~4 files)

- `TechStackList.vue` — Page shell, store coordination, pagination
- `TechStackFilters.vue` — Filter controls (framework, provider, status, grouping)
- `TechStackTable.vue` — Table rendering with sorting
- Extract grouping/aggregation logic into `useTechStackGrouping.ts` composable

### 5. Split ProjectDetail.vue (849 → ~4 files)

- `ProjectDetail.vue` — Page shell, tab navigation, store loading
- `ProjectTechStacksTab.vue` — Tech stack list for project
- `ProjectDependenciesTab.vue` — Dependency list for project
- `ProjectMergeRequestsTab.vue` — MR list for project

### 6. Split DependencyList.vue (768 → ~3 files)

- `DependencyList.vue` — Page shell, store coordination, pagination
- `DependencyFilters.vue` — Filter controls (package manager, type, status, project)
- `DependencyHealthScore.vue` — Health score card + gap statistics

### 7. Extract PDF utilities

- `src/shared/utils/pdfExport.ts` — Common PDF table/header/footer helpers
- Simplify `techStackPdfExport.ts` and `dependencyPdfExport.ts` to use shared helpers

## Constraints

- No new dependencies (use only what's already installed)
- No visual changes — output must be pixel-identical
- All 147 existing Vitest tests must pass
- ESLint + Prettier must pass
- No changes to API contracts or store interfaces

## Success Criteria

- [ ] No source file exceeds 300 lines
- [ ] `useForm`, `useListFiltering`, `useLocalStorage` composables exist and are typed
- [ ] TechStackList, ProjectDetail, DependencyList are split into sub-components
- [ ] PDF export shares common utilities
- [ ] `pnpm lint && pnpm format:check && pnpm test` all pass
- [ ] No direct `localStorage.getItem/setItem` calls outside `useLocalStorage`
