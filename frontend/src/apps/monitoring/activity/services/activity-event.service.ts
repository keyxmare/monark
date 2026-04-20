import type {
  ActivityEvent,
  CreateActivityEventInput,
} from '@/apps/monitoring/activity/types/activity-event';
import { createCrudService } from '@/hub/shared/services/createCrudService';

const crud = createCrudService<ActivityEvent, CreateActivityEventInput>(
  '/monitoring/activity/events',
);

export const activityEventService = {
  list: crud.list,
  get: crud.get,
  create: crud.create,
};
