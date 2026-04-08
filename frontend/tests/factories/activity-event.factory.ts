import type { ActivityEvent } from '@/activity/types/activity-event';

export function createActivityEvent(overrides?: Partial<ActivityEvent>): ActivityEvent {
  return {
    id: 'event-1',
    type: 'project.created',
    entityType: 'project',
    entityId: 'project-1',
    payload: {},
    occurredAt: '2025-01-01T00:00:00+00:00',
    userId: 'user-1',
    ...overrides,
  };
}
