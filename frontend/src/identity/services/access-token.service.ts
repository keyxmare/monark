import type { AccessToken, CreateAccessTokenInput } from '@/identity/types/access-token';
import { createCrudService } from '@/shared/services/createCrudService';

const crud = createCrudService<AccessToken, CreateAccessTokenInput>('/identity/access-tokens');

export const accessTokenService = {
  list: crud.list,
  get: crud.get,
  create: crud.create,
  remove: crud.remove,
};
