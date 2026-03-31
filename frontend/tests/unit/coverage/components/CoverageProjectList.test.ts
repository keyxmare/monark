import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

vi.mock('vue-router', () => ({
  useRouter: () => ({ push: vi.fn() }),
}));

import CoverageProjectList from '@/coverage/components/CoverageProjectList.vue';
import type { CoverageProject } from '@/coverage/types';

const projects: CoverageProject[] = [
  {
    projectId: '1',
    projectName: 'Alpha',
    projectSlug: 'alpha',
    coveragePercent: 85,
    trend: 1.2,
    source: 'gitlab',
    commitHash: 'abc1234567890',
    ref: 'main',
    syncedAt: '2024-01-15T10:00:00Z',
  },
  {
    projectId: '2',
    projectName: 'Beta',
    projectSlug: 'beta',
    coveragePercent: 55,
    trend: -0.5,
    source: 'github',
    commitHash: 'def9876543210',
    ref: 'main',
    syncedAt: '2024-01-14T08:00:00Z',
  },
  {
    projectId: '3',
    projectName: 'Gamma',
    projectSlug: 'gamma',
    coveragePercent: null,
    trend: null,
    source: null,
    commitHash: null,
    ref: null,
    syncedAt: null,
  },
];

describe('CoverageProjectList', () => {
  it('renders the list container', () => {
    const wrapper = mount(CoverageProjectList, { props: { projects } });
    expect(wrapper.find('[data-testid="coverage-project-list"]').exists()).toBe(true);
  });

  it('renders a row for each project', () => {
    const wrapper = mount(CoverageProjectList, { props: { projects } });
    const rows = wrapper.findAll('[data-testid="coverage-project-row"]');
    expect(rows).toHaveLength(3);
  });

  it('displays project names', () => {
    const wrapper = mount(CoverageProjectList, { props: { projects } });
    expect(wrapper.text()).toContain('Alpha');
    expect(wrapper.text()).toContain('Beta');
    expect(wrapper.text()).toContain('Gamma');
  });

  it('displays coverage percentages', () => {
    const wrapper = mount(CoverageProjectList, { props: { projects } });
    expect(wrapper.text()).toContain('85%');
    expect(wrapper.text()).toContain('55%');
  });

  it('truncates commit hashes to 7 characters', () => {
    const wrapper = mount(CoverageProjectList, { props: { projects } });
    const commits = wrapper.findAll('[data-testid="coverage-commit"]');
    expect(commits[0].text()).toBe('abc1234');
    expect(commits[1].text()).toBe('def9876');
  });

  it('shows em dash for null commit hash', () => {
    const wrapper = mount(CoverageProjectList, { props: { projects } });
    const commits = wrapper.findAll('[data-testid="coverage-commit"]');
    expect(commits[2].text()).toBe('—');
  });

  it('shows coverage bar for projects with coverage', () => {
    const wrapper = mount(CoverageProjectList, { props: { projects } });
    const bars = wrapper.findAll('[data-testid="coverage-bar"]');
    expect(bars).toHaveLength(2);
  });

  it('applies green bar class for coverage >= 80%', () => {
    const wrapper = mount(CoverageProjectList, { props: { projects } });
    const bars = wrapper.findAll('[data-testid="coverage-bar"]');
    expect(bars[0].classes()).toContain('bg-green-500');
  });

  it('applies red bar class for coverage < 60%', () => {
    const wrapper = mount(CoverageProjectList, { props: { projects } });
    const bars = wrapper.findAll('[data-testid="coverage-bar"]');
    expect(bars[1].classes()).toContain('bg-red-500');
  });

  it('shows empty state when no projects', () => {
    const wrapper = mount(CoverageProjectList, { props: { projects: [] } });
    expect(wrapper.find('[data-testid="coverage-empty"]').exists()).toBe(true);
  });

  it('does not show empty state when projects exist', () => {
    const wrapper = mount(CoverageProjectList, { props: { projects } });
    expect(wrapper.find('[data-testid="coverage-empty"]').exists()).toBe(false);
  });
});
