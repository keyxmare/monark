import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import CoverageSummaryCard from '@/coverage/components/CoverageSummaryCard.vue';
import type { CoverageSummary } from '@/coverage/types';

const baseSummary: CoverageSummary = {
  averageCoverage: 75.4,
  totalProjects: 10,
  coveredProjects: 8,
  aboveThreshold: 5,
  belowThreshold: 3,
  trend: 2.1,
};

describe('CoverageSummaryCard', () => {
  it('renders the summary container', () => {
    const wrapper = mount(CoverageSummaryCard, { props: { summary: baseSummary } });
    expect(wrapper.find('[data-testid="coverage-summary"]').exists()).toBe(true);
  });

  it('displays the average coverage as percentage', () => {
    const wrapper = mount(CoverageSummaryCard, { props: { summary: baseSummary } });
    expect(wrapper.text()).toContain('75.4%');
  });

  it('displays em dash when averageCoverage is null', () => {
    const wrapper = mount(CoverageSummaryCard, {
      props: { summary: { ...baseSummary, averageCoverage: null } },
    });
    expect(wrapper.text()).toContain('—');
  });

  it('displays covered / total projects count', () => {
    const wrapper = mount(CoverageSummaryCard, { props: { summary: baseSummary } });
    expect(wrapper.text()).toContain('8 / 10');
  });

  it('displays aboveThreshold count', () => {
    const wrapper = mount(CoverageSummaryCard, { props: { summary: baseSummary } });
    expect(wrapper.text()).toContain('5');
  });

  it('displays belowThreshold count', () => {
    const wrapper = mount(CoverageSummaryCard, { props: { summary: baseSummary } });
    expect(wrapper.text()).toContain('3');
  });

  it('shows positive trend with green class', () => {
    const wrapper = mount(CoverageSummaryCard, { props: { summary: baseSummary } });
    const spans = wrapper.findAll('.text-green-500');
    const trendSpan = spans.find((s) => s.text().includes('+2.1'));
    expect(trendSpan).toBeDefined();
    expect(trendSpan!.text()).toContain('+2.1');
  });

  it('shows negative trend with red class', () => {
    const wrapper = mount(CoverageSummaryCard, {
      props: { summary: { ...baseSummary, trend: -1.5 } },
    });
    const span = wrapper.find('.text-red-500');
    expect(span.exists()).toBe(true);
    expect(span.text()).toContain('-1.5');
  });

  it('shows em dash when trend is null', () => {
    const wrapper = mount(CoverageSummaryCard, {
      props: { summary: { ...baseSummary, trend: null } },
    });
    expect(wrapper.find('span.text-green-500').exists()).toBe(false);
    expect(wrapper.find('span.text-red-500').exists()).toBe(false);
  });
});
