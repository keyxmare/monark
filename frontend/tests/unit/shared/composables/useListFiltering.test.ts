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
    await nextTick();
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
    await nextTick();
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
    await nextTick();
    vi.advanceTimersByTime(300);
    await nextTick();
    search.value = '';
    await nextTick();
    vi.advanceTimersByTime(300);
    await nextTick();
    expect(sorted.value).toHaveLength(3);
  });
});
