export type NotificationChannel = 'in_app' | 'email'

export interface Notification {
  id: string
  title: string
  message: string
  channel: NotificationChannel
  readAt: string | null
  userId: string
  createdAt: string
}

export interface CreateNotificationInput {
  title: string
  message: string
  channel: NotificationChannel
  userId: string
}
