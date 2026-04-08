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
