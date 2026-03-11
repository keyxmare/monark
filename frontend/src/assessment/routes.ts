import type { RouteRecordRaw } from 'vue-router'

export const assessmentRoutes: RouteRecordRaw[] = [
  {
    component: () => import('@/assessment/pages/QuizList.vue'),
    meta: { layout: 'dashboard' },
    name: 'assessment-quizzes-list',
    path: '/assessment/quizzes',
  },
  {
    component: () => import('@/assessment/pages/QuizForm.vue'),
    meta: { layout: 'dashboard' },
    name: 'assessment-quizzes-create',
    path: '/assessment/quizzes/new',
  },
  {
    component: () => import('@/assessment/pages/QuizDetail.vue'),
    meta: { layout: 'dashboard' },
    name: 'assessment-quizzes-detail',
    path: '/assessment/quizzes/:id',
  },
  {
    component: () => import('@/assessment/pages/QuizForm.vue'),
    meta: { layout: 'dashboard' },
    name: 'assessment-quizzes-edit',
    path: '/assessment/quizzes/:id/edit',
  },
  {
    component: () => import('@/assessment/pages/QuestionList.vue'),
    meta: { layout: 'dashboard' },
    name: 'assessment-questions-list',
    path: '/assessment/questions',
  },
  {
    component: () => import('@/assessment/pages/QuestionForm.vue'),
    meta: { layout: 'dashboard' },
    name: 'assessment-questions-create',
    path: '/assessment/questions/new',
  },
  {
    component: () => import('@/assessment/pages/QuestionDetail.vue'),
    meta: { layout: 'dashboard' },
    name: 'assessment-questions-detail',
    path: '/assessment/questions/:id',
  },
  {
    component: () => import('@/assessment/pages/QuestionForm.vue'),
    meta: { layout: 'dashboard' },
    name: 'assessment-questions-edit',
    path: '/assessment/questions/:id/edit',
  },
  {
    component: () => import('@/assessment/pages/AnswerList.vue'),
    meta: { layout: 'dashboard' },
    name: 'assessment-answers-list',
    path: '/assessment/answers',
  },
  {
    component: () => import('@/assessment/pages/AnswerForm.vue'),
    meta: { layout: 'dashboard' },
    name: 'assessment-answers-create',
    path: '/assessment/answers/new',
  },
  {
    component: () => import('@/assessment/pages/AnswerForm.vue'),
    meta: { layout: 'dashboard' },
    name: 'assessment-answers-edit',
    path: '/assessment/answers/:id/edit',
  },
  {
    component: () => import('@/assessment/pages/AttemptList.vue'),
    meta: { layout: 'dashboard' },
    name: 'assessment-attempts-list',
    path: '/assessment/attempts',
  },
  {
    component: () => import('@/assessment/pages/AttemptDetail.vue'),
    meta: { layout: 'dashboard' },
    name: 'assessment-attempts-detail',
    path: '/assessment/attempts/:id',
  },
]
