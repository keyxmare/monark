<?php

declare(strict_types=1);

use App\Assessment\Application\DTO\QuestionOutput;
use App\Assessment\Application\Query\GetQuestionQuery;
use App\Assessment\Application\QueryHandler\GetQuestionHandler;
use App\Assessment\Domain\Model\Question;
use App\Assessment\Domain\Model\QuestionLevel;
use App\Assessment\Domain\Model\QuestionType;
use App\Assessment\Domain\Model\Quiz;
use App\Assessment\Domain\Model\QuizType;
use App\Assessment\Domain\Repository\QuestionRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Uid\Uuid;

function stubGetQuestionRepo(?Question $question = null): QuestionRepositoryInterface
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

describe('GetQuestionHandler', function () {
    it('returns a question by id', function () {
        $quiz = Quiz::create(title: 'PHP', slug: 'php', description: 'Quiz', type: QuizType::Quiz);
        $question = Question::create(
            type: QuestionType::SingleChoice,
            content: 'What is PHP?',
            level: QuestionLevel::Easy,
            score: 1,
            position: 1,
            quiz: $quiz,
        );

        $handler = new GetQuestionHandler(\stubGetQuestionRepo($question));
        $result = $handler(new GetQuestionQuery($question->getId()->toRfc4122()));

        expect($result)->toBeInstanceOf(QuestionOutput::class);
        expect($result->content)->toBe('What is PHP?');
    });

    it('throws not found when question does not exist', function () {
        $handler = new GetQuestionHandler(\stubGetQuestionRepo(null));
        $handler(new GetQuestionQuery('00000000-0000-0000-0000-000000000000'));
    })->throws(NotFoundException::class);
});
