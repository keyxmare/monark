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
  }, { deep: true, flush: 'sync' });

  return data;
}
