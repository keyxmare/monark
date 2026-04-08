import { describe, expect, it, vi } from 'vitest';

import { useConfirmDelete } from '@/shared/composables/useConfirmDelete';

describe('useConfirmDelete', () => {
  it('starts with null target and closed', () => {
    const { isOpen, target } = useConfirmDelete();
    expect(target.value).toBeNull();
    expect(isOpen.value).toBe(false);
  });

  it('requestDelete sets target and opens', () => {
    const { isOpen, requestDelete, target } = useConfirmDelete<{ id: string }>();
    requestDelete({ id: '1' });
    expect(target.value).toEqual({ id: '1' });
    expect(isOpen.value).toBe(true);
  });

  it('cancel clears target', () => {
    const { cancel, isOpen, requestDelete } = useConfirmDelete();
    requestDelete('item');
    cancel();
    expect(isOpen.value).toBe(false);
  });

  it('confirm calls deleteFn and clears target', async () => {
    const deleteFn = vi.fn().mockResolvedValue(undefined);
    const { confirm, isOpen, requestDelete } = useConfirmDelete();
    requestDelete('item');
    await confirm(deleteFn);
    expect(deleteFn).toHaveBeenCalled();
    expect(isOpen.value).toBe(false);
  });
});
