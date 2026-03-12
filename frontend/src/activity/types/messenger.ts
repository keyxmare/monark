export interface QueueStats {
  name: string
  messages: number
  messages_ready: number
  messages_unacknowledged: number
  consumers: number
  publish_rate: number
  deliver_rate: number
}

export interface WorkerStats {
  connection: string
  prefetch: number
  state: string
}

export interface MessengerStats {
  queues: QueueStats[]
  workers: WorkerStats[]
}
