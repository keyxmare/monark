import type { UpdateUserInput, User } from '@/hub/identity/types/user';
import { createCrudService } from '@/hub/shared/services/createCrudService';

const crud = createCrudService<User, never, UpdateUserInput>('/hub/identity/users');

export const userService = {
  list: crud.list,
  get: crud.get,
  update: crud.update,
};
