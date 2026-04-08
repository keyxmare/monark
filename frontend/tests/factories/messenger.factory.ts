import type { MessengerStats, QueueStats, WorkerStats } from '@/activity/types/messenger';

export function createQueueStats(overrides?: Partial<QueueStats>): QueueStats {
  return {
    name: 'async',
    messages: 10,
    messages_ready: 8,
    messages_unacknowledged: 2,
    consumers: 1,
    publish_rate: 5.0,
    deliver_rate: 4.5,
    ...overrides,
  };
}

export function createWorkerStats(overrides?: Partial<WorkerStats>): WorkerStats {
  return {
    connection: 'amqp://localhost:5672',
    prefetch: 10,
    state: 'running',
    ...overrides,
  };
}

export function createMessengerStats(overrides?: Partial<MessengerStats>): MessengerStats {
  return {
    queues: [createQueueStats()],
    workers: [createWorkerStats()],
    ...overrides,
  };
}
