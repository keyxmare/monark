export type QuestionType = 'single_choice' | 'multiple_choice' | 'text' | 'code'
export type QuestionLevel = 'easy' | 'medium' | 'hard'

export interface Question {
  id: string
  type: QuestionType
  content: string
  level: QuestionLevel
  score: number
  position: number
  quizId: string
  answerCount: number
  createdAt: string
  updatedAt: string
}

export interface CreateQuestionInput {
  type: QuestionType
  content: string
  level: QuestionLevel
  score: number
  position: number
  quizId: string
}

export interface UpdateQuestionInput {
  type?: QuestionType
  content?: string
  level?: QuestionLevel
  score?: number
  position?: number
}
