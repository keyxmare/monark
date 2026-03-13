<?php

declare(strict_types=1);

use App\Assessment\Application\Command\CreateAnswerCommand;
use App\Assessment\Application\CommandHandler\CreateAnswerHandler;
use App\Assessment\Application\DTO\AnswerOutput;
use App\Assessment\Application\DTO\CreateAnswerInput;
use App\Assessment\Domain\Model\Answer;
use App\Assessment\Domain\Model\Question;
use App\Assessment\Domain\Model\QuestionLevel;
use App\Assessment\Domain\Model\QuestionType;
use App\Assessment\Domain\Model\Quiz;
use App\Assessment\Domain\Model\QuizType;
use App\Assessment\Domain\Repository\AnswerRepositoryInterface;
use App\Assessment\Domain\Repository\QuestionRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Uid\Uuid;

function stubCreateAnswerQuestionRepo(?Question $question = null): QuestionRepositoryInterface
{
    return new class ($question) implements QuestionRepositoryInterface {
        public function __construct(private readonly ?Question $question)
        {
        }
        public function findById(Uuid $id): ?Question
        {
            return $this->question;
        }
        public function findAll(int $page = 1, int $perPage = 20): array
        {
            return [];
        }
        public function findByQuizId(Uuid $quizId, int $page = 1, int $perPage = 20): array
        {
            return [];
        }
        public function count(): int
        {
            return 0;
        }
        public function countByQuizId(Uuid $quizId): int
        {
            return 0;
        }
        public function save(Question $question): void
        {
        }
        public function delete(Question $question): void
        {
        }
    };
}

function stubCreateAnswerRepo(): AnswerRepositoryInterface
{
    return new class () implements AnswerRepositoryInterface {
        public ?Answer $saved = null;
        public function findById(Uuid $id): ?Answer
        {
            return null;
        }
        public function findAll(int $page = 1, int $perPage = 20): array
        {
            return [];
        }
        public function findByQuestionId(Uuid $questionId, int $page = 1, int $perPage = 20): array
        {
            return [];
        }
        public function count(): int
        {
            return 0;
        }
        public function countByQuestionId(Uuid $questionId): int
        {
            return 0;
        }
        public function save(Answer $answer): void
        {
            $this->saved = $answer;
        }
        public function delete(Answer $answer): void
        {
        }
    };
}

describe('CreateAnswerHandler', function () {
    it('creates an answer successfully', function () {
        $quiz = Quiz::create(title: 'PHP', slug: 'php', description: 'Quiz', type: QuizType::Quiz);
        $question = Question::create(
            type: QuestionType::SingleChoice,
            content: 'What is PHP?',
            level: QuestionLevel::Easy,
            score: 1,
            position: 1,
            quiz: $quiz,
        );

        $answerRepo = \stubCreateAnswerRepo();
        $handler = new CreateAnswerHandler($answerRepo, \stubCreateAnswerQuestionRepo($question));

        $input = new CreateAnswerInput(
            content: 'A programming language',
            isCorrect: true,
            position: 1,
            questionId: $question->getId()->toRfc4122(),
        );

        $result = $handler(new CreateAnswerCommand($input));

        expect($result)->toBeInstanceOf(AnswerOutput::class);
        expect($result->content)->toBe('A programming language');
        expect($result->isCorrect)->toBeTrue();
        expect($answerRepo->saved)->not->toBeNull();
    });

    it('throws not found when question does not exist', function () {
        $handler = new CreateAnswerHandler(\stubCreateAnswerRepo(), \stubCreateAnswerQuestionRepo(null));

        $input = new CreateAnswerInput(
            content: 'Answer text',
            isCorrect: false,
            position: 1,
            questionId: '00000000-0000-0000-0000-000000000000',
        );
        $handler(new CreateAnswerCommand($input));
    })->throws(NotFoundException::class);
});
