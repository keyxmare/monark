import { computed, reactive } from 'vue';

export interface UseFormOptions<T> {
  required?: (keyof T)[];
}

export function useForm<T extends Record<string, unknown>>(
  initialValues: T,
  options: UseFormOptions<T> = {},
) {
  const { required = [] } = options;

  const fields = reactive({ ...initialValues }) as T;
  const touched = reactive(
    Object.fromEntries(Object.keys(initialValues).map((k) => [k, false])),
  ) as Record<keyof T, boolean>;

  function touch(field: keyof T) {
    touched[field] = true;
  }

  function touchAll() {
    for (const key of Object.keys(touched)) {
      touched[key as keyof T] = true;
    }
  }

  const errors = computed(() => {
    const result = {} as Record<keyof T, boolean>;
    for (const key of Object.keys(fields as Record<string, unknown>)) {
      const k = key as keyof T;
      const isEmpty = fields[k] === '' || fields[k] === null || fields[k] === undefined;
      result[k] = touched[k] && required.includes(k) && isEmpty;
    }
    return result;
  });

  const isValid = computed(() => {
    return required.every((k) => {
      const val = fields[k];
      return val !== '' && val !== null && val !== undefined;
    });
  });

  async function handleSubmit(callback: () => Promise<void> | void) {
    touchAll();
    if (!isValid.value) return;
    await callback();
  }

  function reset(values?: T) {
    const source = values ?? initialValues;
    for (const key of Object.keys(source as Record<string, unknown>)) {
      const k = key as keyof T;
      (fields as Record<string, unknown>)[k as string] = source[k];
      touched[k] = false;
    }
  }

  return { errors, fields, handleSubmit, isValid, reset, touch, touchAll, touched };
}
