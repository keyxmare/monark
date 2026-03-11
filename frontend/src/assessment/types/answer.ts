export interface Answer {
  id: string
  content: string
  isCorrect: boolean
  position: number
  questionId: string
  createdAt: string
  updatedAt: string
}

export interface CreateAnswerInput {
  content: string
  isCorrect: boolean
  position: number
  questionId: string
}

export interface UpdateAnswerInput {
  content?: string
  isCorrect?: boolean
  position?: number
}
