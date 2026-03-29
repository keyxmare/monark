import type { ActivityEvent, CreateActivityEventInput } from '@/activity/types/activity-event';
import { createCrudService } from '@/shared/services/createCrudService';

const crud = createCrudService<ActivityEvent, CreateActivityEventInput>('/activity/events');

export const activityEventService = {
  list: crud.list,
  get: crud.get,
  create: crud.create,
};
