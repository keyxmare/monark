<?php

declare(strict_types=1);

use App\Assessment\Application\DTO\QuestionListOutput;
use App\Assessment\Application\Query\ListQuestionsQuery;
use App\Assessment\Application\QueryHandler\ListQuestionsHandler;
use App\Assessment\Domain\Model\Question;
use App\Assessment\Domain\Model\QuestionLevel;
use App\Assessment\Domain\Model\QuestionType;
use App\Assessment\Domain\Model\Quiz;
use App\Assessment\Domain\Model\QuizType;
use App\Assessment\Domain\Repository\QuestionRepositoryInterface;
use Symfony\Component\Uid\Uuid;

function stubListQuestionsRepo(array $questions = [], int $count = 0): QuestionRepositoryInterface
{
    return new class ($questions, $count) implements QuestionRepositoryInterface {
        public function __construct(private readonly array $questions, private readonly int $count) {}
        public function findById(Uuid $id): ?Question { return null; }
        public function findAll(int $page = 1, int $perPage = 20): array { return $this->questions; }
        public function findByQuizId(Uuid $quizId, int $page = 1, int $perPage = 20): array { return $this->questions; }
        public function count(): int { return $this->count; }
        public function countByQuizId(Uuid $quizId): int { return $this->count; }
        public function save(Question $question): void {}
        public function delete(Question $question): void {}
    };
}

describe('ListQuestionsHandler', function () {
    it('returns paginated questions', function () {
        $quiz = Quiz::create(title: 'PHP', slug: 'php', description: 'Quiz', type: QuizType::Quiz);
        $q1 = Question::create(type: QuestionType::SingleChoice, content: 'Q1', level: QuestionLevel::Easy, score: 1, position: 1, quiz: $quiz);
        $q2 = Question::create(type: QuestionType::Text, content: 'Q2', level: QuestionLevel::Hard, score: 2, position: 2, quiz: $quiz);

        $handler = new ListQuestionsHandler(stubListQuestionsRepo([$q1, $q2], 2));
        $result = $handler(new ListQuestionsQuery(1, 20));

        expect($result)->toBeInstanceOf(QuestionListOutput::class);
        expect($result->pagination->items)->toHaveCount(2);
        expect($result->pagination->total)->toBe(2);
    });

    it('returns empty list when no questions', function () {
        $handler = new ListQuestionsHandler(stubListQuestionsRepo([], 0));
        $result = $handler(new ListQuestionsQuery());

        expect($result->pagination->items)->toBeEmpty();
        expect($result->pagination->total)->toBe(0);
    });
});
