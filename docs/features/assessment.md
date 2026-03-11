# Assessment

Quiz management, multi-type questions, and attempt tracking.

## Features

### Quiz Management
- Create, edit, publish, and archive quizzes
- Schedule start/end dates and time limits
- Quiz types: quiz (scored) and survey (unscored)

### Questions
- Multiple question types: single choice, multiple choice, text, code
- Difficulty levels with scoring
- Ordered positioning

### Attempts
- Start and submit quiz attempts
- Automatic and manual grading
- Score tracking and progression

## Entities

- **Quiz**: title, slug, description, type, status, startsAt, endsAt, timeLimit, authorId
- **Question**: type, content, level, score, position
- **Answer**: content, isCorrect, position
- **Attempt**: score, status (started/submitted/graded), startedAt, finishedAt, userId, quizId
