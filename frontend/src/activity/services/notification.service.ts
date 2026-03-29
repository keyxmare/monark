import type { ApiResponse } from '@/shared/types';
import type { CreateNotificationInput, Notification } from '@/activity/types/notification';
import { createCrudService } from '@/shared/services/createCrudService';
import { api } from '@/shared/utils/api';

const BASE_URL = '/activity/notifications';

const crud = createCrudService<Notification, CreateNotificationInput>(BASE_URL);

export const notificationService = {
  list: crud.list,
  get: crud.get,
  create: crud.create,

  markAsRead(id: string): Promise<ApiResponse<Notification>> {
    return api.put<ApiResponse<Notification>>(`${BASE_URL}/${id}`, {});
  },
};
