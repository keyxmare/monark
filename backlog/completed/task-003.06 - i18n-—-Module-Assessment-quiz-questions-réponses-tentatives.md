---
id: TASK-003.06
title: 'i18n — Module Assessment (quiz, questions, réponses, tentatives)'
status: Done
assignee: []
created_date: '2026-03-11 19:33'
updated_date: '2026-03-11 20:02'
labels:
  - i18n
  - frontend
dependencies:
  - TASK-003.02
parent_task_id: TASK-003
priority: medium
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
### Objectif

Extraire tous les textes hardcodés des pages Assessment (~10 pages Vue).

### Fichiers : QuizList, QuizDetail, QuizForm, QuestionList, QuestionDetail, QuestionForm, AnswerList, AnswerForm, AttemptList, AttemptDetail

### Clés (~50)
- `assessment.quizzes.*` : Quizzes, Title, Type (quiz/survey), Status (draft/published/archived)...
- `assessment.questions.*` : Type (single_choice/multiple_choice/text/code), Level (easy/medium/hard)...
- `assessment.answers.*` : Correct, Yes/No...
- `assessment.attempts.*` : Score, Status (started/submitted/graded)...
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Tous les textes hardcodés des pages Assessment extraits
- [ ] #2 Clés `assessment.*` ajoutées dans en.json et fr.json
- [ ] #3 Enums type, status, level traduits
- [ ] #4 0 texte anglais en dur dans assessment/pages/
- [ ] #5 Tests passent
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Translated all 10 Assessment module pages (QuizList, QuizDetail, QuizForm, QuestionList, QuestionDetail, QuestionForm, AnswerList, AnswerForm, AttemptList, AttemptDetail) with vue-i18n. Added ~80 assessment.quizzes.*, assessment.questions.*, assessment.answers.*, and assessment.attempts.* locale keys in both en.json and fr.json. Build passes cleanly.
<!-- SECTION:FINAL_SUMMARY:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer; Coverage 80% minimum; Mutation 80% minimum; Documentation mise à jour;
<!-- DOD:END -->
