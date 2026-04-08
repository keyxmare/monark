import type { UpdateUserInput, User } from '@/identity/types/user';
import { createCrudService } from '@/shared/services/createCrudService';

const crud = createCrudService<User, never, UpdateUserInput>('/identity/users');

export const userService = {
  list: crud.list,
  get: crud.get,
  update: crud.update,
};
