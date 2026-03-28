import type { ApiResponse } from '@/shared/types';
import { api } from '@/shared/utils/api';
import type { MessengerStats } from '@/activity/types/messenger';

export const messengerService = {
  getStats(): Promise<ApiResponse<MessengerStats>> {
    return api.get<ApiResponse<MessengerStats>>('/activity/messenger/stats');
  },
};
