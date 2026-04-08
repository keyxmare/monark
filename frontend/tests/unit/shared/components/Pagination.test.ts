import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

vi.mock('vue-i18n', () => ({
  useI18n: () => ({
    t: (key: string, params?: Record<string, unknown>) =>
      params ? `${key} ${JSON.stringify(params)}` : key,
  }),
}));

import Pagination from '@/shared/components/Pagination.vue';

function mountPagination(props: { page: number; totalPages: number; total?: number }) {
  return mount(Pagination, { props });
}

describe('Pagination', () => {
  it('renders pagination controls', () => {
    const wrapper = mountPagination({ page: 1, totalPages: 5 });
    expect(wrapper.find('[data-testid="pagination"]').exists()).toBe(true);
  });

  it('displays page info', () => {
    const wrapper = mountPagination({ page: 2, totalPages: 5 });
    expect(wrapper.find('[data-testid="pagination-info"]').text()).toContain(
      'common.pagination.page',
    );
  });

  it('disables prev button on first page', () => {
    const wrapper = mountPagination({ page: 1, totalPages: 5 });
    expect(wrapper.find('[data-testid="pagination-prev"]').attributes('disabled')).toBeDefined();
  });

  it('disables next button on last page', () => {
    const wrapper = mountPagination({ page: 5, totalPages: 5 });
    expect(wrapper.find('[data-testid="pagination-next"]').attributes('disabled')).toBeDefined();
  });

  it('enables both buttons on middle page', () => {
    const wrapper = mountPagination({ page: 3, totalPages: 5 });
    expect(wrapper.find('[data-testid="pagination-prev"]').attributes('disabled')).toBeUndefined();
    expect(wrapper.find('[data-testid="pagination-next"]').attributes('disabled')).toBeUndefined();
  });

  it('emits update:page with page-1 on prev click', async () => {
    const wrapper = mountPagination({ page: 3, totalPages: 5 });
    await wrapper.find('[data-testid="pagination-prev"]').trigger('click');
    expect(wrapper.emitted('update:page')?.[0]).toEqual([2]);
  });

  it('emits update:page with page+1 on next click', async () => {
    const wrapper = mountPagination({ page: 3, totalPages: 5 });
    await wrapper.find('[data-testid="pagination-next"]').trigger('click');
    expect(wrapper.emitted('update:page')?.[0]).toEqual([4]);
  });

  it('shows total items when provided', () => {
    const wrapper = mountPagination({ page: 1, totalPages: 5, total: 42 });
    expect(wrapper.find('[data-testid="pagination-total"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="pagination-total"]').text()).toContain('42');
  });

  it('hides total items when not provided', () => {
    const wrapper = mountPagination({ page: 1, totalPages: 5 });
    expect(wrapper.find('[data-testid="pagination-total"]').exists()).toBe(false);
  });
});
