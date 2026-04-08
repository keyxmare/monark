import { describe, expect, it, vi } from 'vitest';

import { useForm } from '@/shared/composables/useForm';

describe('useForm', () => {
  const initialValues = { email: '', name: '' };

  it('initializes with provided values', () => {
    const { fields } = useForm(initialValues);
    expect(fields.name).toBe('');
    expect(fields.email).toBe('');
  });

  it('tracks touched state per field', () => {
    const { touch, touched } = useForm(initialValues);
    expect(touched.name).toBe(false);
    touch('name');
    expect(touched.name).toBe(true);
    expect(touched.email).toBe(false);
  });

  it('touchAll marks all fields as touched', () => {
    const { touchAll, touched } = useForm(initialValues);
    touchAll();
    expect(touched.name).toBe(true);
    expect(touched.email).toBe(true);
  });

  it('reports required field errors when touched and empty', () => {
    const { errors, touchAll } = useForm(initialValues, {
      required: ['name', 'email'],
    });
    touchAll();
    expect(errors.value.name).toBe(true);
    expect(errors.value.email).toBe(true);
  });

  it('clears error when field is filled', () => {
    const { errors, fields, touchAll } = useForm(initialValues, {
      required: ['name'],
    });
    touchAll();
    expect(errors.value.name).toBe(true);
    fields.name = 'John';
    expect(errors.value.name).toBe(false);
  });

  it('isValid is true when all required fields are filled', () => {
    const { fields, isValid } = useForm(initialValues, {
      required: ['name'],
    });
    fields.name = 'John';
    expect(isValid.value).toBe(true);
  });

  it('isValid is false when a required field is empty', () => {
    const { isValid } = useForm(initialValues, {
      required: ['name'],
    });
    expect(isValid.value).toBe(false);
  });

  it('handleSubmit calls callback when valid', async () => {
    const callback = vi.fn();
    const { fields, handleSubmit } = useForm(initialValues, {
      required: ['name'],
    });
    fields.name = 'John';
    await handleSubmit(callback);
    expect(callback).toHaveBeenCalled();
  });

  it('handleSubmit does not call callback when invalid', async () => {
    const callback = vi.fn();
    const { handleSubmit, touched } = useForm(initialValues, {
      required: ['name'],
    });
    await handleSubmit(callback);
    expect(callback).not.toHaveBeenCalled();
    expect(touched.name).toBe(true);
  });

  it('reset restores initial values and clears touched', () => {
    const { fields, reset, touch, touched } = useForm(initialValues);
    fields.name = 'John';
    touch('name');
    reset();
    expect(fields.name).toBe('');
    expect(touched.name).toBe(false);
  });

  it('reset with new values uses those instead', () => {
    const { fields, reset } = useForm(initialValues);
    reset({ email: 'new@test.com', name: 'New' });
    expect(fields.name).toBe('New');
    expect(fields.email).toBe('new@test.com');
  });
});
