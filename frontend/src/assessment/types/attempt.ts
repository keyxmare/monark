export type AttemptStatus = 'started' | 'submitted' | 'graded'

export interface Attempt {
  id: string
  score: number
  status: AttemptStatus
  startedAt: string
  finishedAt: string | null
  userId: string
  quizId: string
  createdAt: string
}

export interface CreateAttemptInput {
  userId: string
  quizId: string
}
