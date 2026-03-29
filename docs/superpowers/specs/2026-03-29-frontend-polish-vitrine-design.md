# Sub-Project 3: Frontend Polish — Vitrine Technologique

**Date**: 2026-03-29
**Status**: Approved
**Depends on**: Sub-project 1 (composables), Sub-project 2 (tests)

## Context

After composable extraction and comprehensive testing, the frontend needs polish to become a true technological showcase. This means accessibility, performance, and advanced patterns that demonstrate engineering maturity.

## Goals

- WCAG 2.1 AA accessibility compliance on all interactive elements
- Performance optimizations (debounce, caching, virtual scrolling)
- Advanced Vue 3 patterns that demonstrate expertise
- Clean up remaining anti-patterns

## Scope

### 1. Accessibility
- Replace ConfirmDialog with true `<dialog>` element + focus trap
- Add ARIA labels to all sort buttons and interactive elements
- Keyboard navigation for DropdownMenu and ExportDropdown
- Screen reader announcements for toast notifications
- Skip-to-content link in DashboardLayout

### 2. Performance
- Debounce search inputs (300ms) via `useListFiltering`
- Request caching in stores (avoid refetch on back navigation)
- Virtual scrolling for large lists (TechStackList, DependencyList)
- Lazy load heavy components (PDF export, charts)

### 3. Advanced Patterns
- Branded types for entity IDs (`ProjectId`, `ProviderId` vs raw `string`)
- Typed route meta with `declare module 'vue-router'` augmentation
- Composable-based error boundary component
- Optimistic updates with rollback on failure

### 4. Anti-Pattern Cleanup
- Replace magic strings with const enums (layout names, status values)
- Max retry limit on Mercure reconnection (currently infinite)
- Centralize date/time formatting utilities

## Constraints

- No new UI framework or component library
- No breaking changes to existing routes or APIs
- All tests from sub-project 2 must stay green

## Success Criteria

- [ ] All interactive elements have ARIA attributes
- [ ] ConfirmDialog uses native `<dialog>` with focus trap
- [ ] Search inputs are debounced
- [ ] No magic strings for statuses or layout names
- [ ] Mercure has max retry limit
- [ ] All tests pass, lint clean
