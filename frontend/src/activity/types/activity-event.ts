export interface ActivityEvent {
  id: string;
  type: string;
  entityType: string;
  entityId: string;
  payload: Record<string, unknown>;
  occurredAt: string;
  userId: string;
}

export interface CreateActivityEventInput {
  type: string;
  entityType: string;
  entityId: string;
  payload: Record<string, unknown>;
  userId: string;
}
