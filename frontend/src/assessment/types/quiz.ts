export type QuizType = 'quiz' | 'survey'
export type QuizStatus = 'draft' | 'published' | 'archived'

export interface Quiz {
  id: string
  title: string
  slug: string
  description: string
  type: QuizType
  status: QuizStatus
  startsAt: string | null
  endsAt: string | null
  timeLimit: number | null
  authorId: string
  questionCount: number
  createdAt: string
  updatedAt: string
}

export interface CreateQuizInput {
  title: string
  slug: string
  description: string
  type: QuizType
  status?: QuizStatus
  startsAt?: string
  endsAt?: string
  timeLimit?: number
  authorId?: string
}

export interface UpdateQuizInput {
  title?: string
  slug?: string
  description?: string
  type?: QuizType
  status?: QuizStatus
  startsAt?: string
  endsAt?: string
  timeLimit?: number
}
