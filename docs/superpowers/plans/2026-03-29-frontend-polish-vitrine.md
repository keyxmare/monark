# Frontend Polish — Vitrine Technologique

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Polish the frontend into a technological showcase with typed routes, keyboard focus traps, debounced inputs, Mercure retry limits, and const enums replacing magic strings.

**Architecture:** Targeted improvements across existing code. Each task is self-contained. No new dependencies. No visual changes except accessibility improvements.

**Tech Stack:** Vue 3 + TypeScript, vue-router, Pinia, Vitest

**Commands:**
- Tests: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend sh -c 'pnpm vitest run'`
- Lint: `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec -T frontend sh -c 'pnpm lint'`

**Working directory:** `/Users/keyxmare/Projects/github.com/keyxmare/monark/frontend`

---

## Task 1: Typed route meta + const enums for layouts

**Files:**
- Create: `src/shared/types/router.ts`
- Create: `src/shared/types/enums.ts`
- Modify: `src/app/router.ts`
- Modify: route files in each context

- [ ] **Step 1: Create typed route meta**

```typescript
// src/shared/types/router.ts
import 'vue-router';

export type LayoutName = 'auth' | 'dashboard';

declare module 'vue-router' {
  interface RouteMeta {
    layout?: LayoutName;
    public?: boolean;
  }
}
```

- [ ] **Step 2: Create const enums for magic strings**

```typescript
// src/shared/types/enums.ts
export const Layout = {
  Auth: 'auth',
  Dashboard: 'dashboard',
} as const;

export const SyncStatus = {
  Completed: 'completed',
  Failed: 'failed',
  Running: 'running',
} as const;

export const MergeRequestState = {
  Closed: 'closed',
  Draft: 'draft',
  Merged: 'merged',
  Open: 'open',
} as const;

export const ImportStatus = {
  Error: 'error',
  Imported: 'imported',
  Importing: 'importing',
  Pending: 'pending',
} as const;
```

- [ ] **Step 3: Update route files to use Layout constants**

Read each route file (`src/activity/routes.ts`, `src/catalog/routes.ts`, `src/dependency/routes.ts`, `src/identity/routes.ts`) and replace `meta: { layout: 'dashboard' }` with `meta: { layout: Layout.Dashboard }`.

- [ ] **Step 4: Update components using magic strings**

Search for string literals `'running'`, `'completed'`, `'failed'`, `'importing'`, `'imported'`, `'pending'`, `'open'`, `'merged'`, `'closed'`, `'draft'` in `.vue` and `.ts` files and replace with enum constants where they represent status checks.

- [ ] **Step 5: Run tests + lint, commit**

```bash
git add src/shared/types/router.ts src/shared/types/enums.ts src/app/router.ts src/*/routes.ts
git commit -m "feat(shared): add typed route meta and const enums for magic strings"
```

---

## Task 2: Focus trap for ConfirmDialog

**Files:**
- Modify: `src/shared/components/ConfirmDialog.vue`
- Modify: `tests/unit/shared/components/ConfirmDialog.test.ts`

Note: ConfirmDialog already uses native `<dialog>` with `showModal()`, which provides built-in focus trapping in modern browsers. We need to add:
- Focus the cancel button when dialog opens (first focusable element)
- Handle Tab/Shift+Tab cycling within the dialog
- `aria-labelledby` and `aria-describedby` attributes

- [ ] **Step 1: Read current ConfirmDialog.vue and its test**

- [ ] **Step 2: Add focus management and ARIA attributes**

In the `watch` for `props.open`, after `showModal()`, call `nextTick` then focus the cancel button. Add `aria-labelledby="confirm-title"` and `aria-describedby="confirm-message"` on the `<dialog>`. Add matching `id` attributes on title and message.

- [ ] **Step 3: Add tests for focus behavior**

Test: when dialog opens, cancel button receives focus. Test: aria attributes are present.

- [ ] **Step 4: Run tests + lint, commit**

```bash
git add src/shared/components/ConfirmDialog.vue tests/unit/shared/components/ConfirmDialog.test.ts
git commit -m "feat(shared): add focus management and ARIA attributes to ConfirmDialog"
```

---

## Task 3: Keyboard navigation for DropdownMenu

**Files:**
- Modify: `src/shared/components/DropdownMenu.vue`
- Modify: `tests/unit/shared/components/DropdownMenu.test.ts`

Note: DropdownMenu already has `aria-expanded`, `role="menu"`, `role="menuitem"`, and Escape handling. We need to add:
- Arrow key navigation (Up/Down to move between items)
- Home/End keys to jump to first/last item
- `aria-activedescendant` tracking
- `tabindex="-1"` on menu items (only focused via arrow keys)

- [ ] **Step 1: Read current DropdownMenu.vue and its test**

- [ ] **Step 2: Add arrow key navigation**

Add a `focusedIndex` ref. On ArrowDown: increment (wrap to 0). On ArrowUp: decrement (wrap to last). On Home: set to 0. On End: set to last. On Enter/Space: select focused item. Update the keydown handler. Add `tabindex="-1"` and conditional `data-focused` on items. Focus the item element via ref.

- [ ] **Step 3: Add tests for keyboard nav**

Test: ArrowDown moves focus to next item. ArrowUp wraps. Enter selects. Escape closes.

- [ ] **Step 4: Run tests + lint, commit**

```bash
git add src/shared/components/DropdownMenu.vue tests/unit/shared/components/DropdownMenu.test.ts
git commit -m "feat(shared): add keyboard navigation to DropdownMenu"
```

---

## Task 4: Mercure max retry limit

**Files:**
- Modify: `src/shared/composables/useMercure.ts`
- Modify: `tests/unit/shared/composables/useMercure.test.ts`

- [ ] **Step 1: Read current useMercure.ts and test**

- [ ] **Step 2: Add max retry limit**

Add `maxRetries` option (default: 5) and `retryCount` ref. Increment on each error reconnect. Stop reconnecting when `retryCount >= maxRetries`. Reset `retryCount` on successful `onopen`. Add `exhausted` ref that becomes true when retries are exhausted.

- [ ] **Step 3: Add tests for retry limit**

Test: stops reconnecting after maxRetries. Test: resets count on successful connection. Test: exhausted ref is true after max retries.

- [ ] **Step 4: Run tests + lint, commit**

```bash
git add src/shared/composables/useMercure.ts tests/unit/shared/composables/useMercure.test.ts
git commit -m "feat(shared): add max retry limit to useMercure composable"
```

---

## Task 5: Skip-to-content link in DashboardLayout

**Files:**
- Modify: `src/shared/layouts/DashboardLayout.vue`

- [ ] **Step 1: Read DashboardLayout.vue**

- [ ] **Step 2: Add skip-to-content link**

Add as first child inside the layout:
```html
<a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:rounded focus:bg-primary focus:px-4 focus:py-2 focus:text-white">
  {{ t('aria.skipToContent') }}
</a>
```

Add `id="main-content"` on the main content area.

- [ ] **Step 3: Add i18n key if missing**

Check if `aria.skipToContent` exists in `src/shared/i18n/locales/en.json` and `fr.json`. Add if missing.

- [ ] **Step 4: Run tests + lint, commit**

```bash
git add src/shared/layouts/DashboardLayout.vue src/shared/i18n/locales/
git commit -m "feat(shared): add skip-to-content link for accessibility"
```

---

## Task 6: ARIA labels for sort buttons

**Files:**
- Modify: `src/shared/composables/useListFiltering.ts`
- Modify: sort headers in list page components

- [ ] **Step 1: Add `sortAriaLabel(field, label)` to useListFiltering**

Returns: `"Sort by {label}, currently {ascending/descending}"` or `"Sort by {label}"` if not active.

- [ ] **Step 2: Update sort `<th>` buttons in list pages**

Add `:aria-label="sortAriaLabel('field', t('column.label'))"` and `aria-sort` attribute to sort headers.

- [ ] **Step 3: Run tests + lint, commit**

```bash
git add src/shared/composables/useListFiltering.ts src/*/pages/*List.vue src/*/components/*Table.vue
git commit -m "feat(shared): add ARIA labels to sort buttons"
```

---

## Task 7: Screen reader announcements for toasts

**Files:**
- Modify: `src/shared/components/AppToastContainer.vue`

- [ ] **Step 1: Read AppToastContainer.vue**

- [ ] **Step 2: Add aria-live region**

Wrap the toast container with `aria-live="polite"` and `role="status"` so screen readers announce new toasts.

- [ ] **Step 3: Run tests + lint, commit**

```bash
git add src/shared/components/AppToastContainer.vue
git commit -m "feat(shared): add aria-live region to toast container"
```

---

## Task 8: Centralize date/time formatting

**Files:**
- Create: `src/shared/utils/dateFormat.ts`
- Create: `tests/unit/shared/utils/dateFormat.test.ts`

- [ ] **Step 1: Write tests**

Test: `formatDate(isoString, locale)` returns localized date. Test: `formatDateTime(isoString, locale)` returns localized date+time. Test: `formatRelative(isoString)` returns "2 hours ago" style.

- [ ] **Step 2: Implement dateFormat utilities**

```typescript
export function formatDate(iso: string, locale = 'fr'): string {
  return new Intl.DateTimeFormat(locale, { dateStyle: 'medium' }).format(new Date(iso));
}

export function formatDateTime(iso: string, locale = 'fr'): string {
  return new Intl.DateTimeFormat(locale, { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(iso));
}

export function formatRelative(iso: string): string {
  const diff = Date.now() - new Date(iso).getTime();
  const rtf = new Intl.RelativeTimeFormat('fr', { numeric: 'auto' });
  const seconds = Math.floor(diff / 1000);
  if (seconds < 60) return rtf.format(-seconds, 'second');
  const minutes = Math.floor(seconds / 60);
  if (minutes < 60) return rtf.format(-minutes, 'minute');
  const hours = Math.floor(minutes / 60);
  if (hours < 24) return rtf.format(-hours, 'hour');
  const days = Math.floor(hours / 24);
  return rtf.format(-days, 'day');
}
```

- [ ] **Step 3: Run tests + lint, commit**

```bash
git add src/shared/utils/dateFormat.ts tests/unit/shared/utils/dateFormat.test.ts
git commit -m "feat(shared): add centralized date/time formatting utilities"
```

---

## Task 9: Final validation

- [ ] **Step 1: Run full test suite**
- [ ] **Step 2: Run lint + format check**
- [ ] **Step 3: Verify no regressions**
- [ ] **Step 4: Push**

```bash
git push origin master
```
