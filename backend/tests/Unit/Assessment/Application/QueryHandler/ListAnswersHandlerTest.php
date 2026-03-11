<?php

declare(strict_types=1);

use App\Assessment\Application\DTO\AnswerListOutput;
use App\Assessment\Application\Query\ListAnswersQuery;
use App\Assessment\Application\QueryHandler\ListAnswersHandler;
use App\Assessment\Domain\Model\Answer;
use App\Assessment\Domain\Model\Question;
use App\Assessment\Domain\Model\QuestionLevel;
use App\Assessment\Domain\Model\QuestionType;
use App\Assessment\Domain\Model\Quiz;
use App\Assessment\Domain\Model\QuizType;
use App\Assessment\Domain\Repository\AnswerRepositoryInterface;
use Symfony\Component\Uid\Uuid;

function stubListAnswersRepo(array $answers = [], int $count = 0): AnswerRepositoryInterface
{
    return new class ($answers, $count) implements AnswerRepositoryInterface {
        public function __construct(private readonly array $answers, private readonly int $count) {}
        public function findById(Uuid $id): ?Answer { return null; }
        public function findAll(int $page = 1, int $perPage = 20): array { return $this->answers; }
        public function findByQuestionId(Uuid $questionId, int $page = 1, int $perPage = 20): array { return $this->answers; }
        public function count(): int { return $this->count; }
        public function countByQuestionId(Uuid $questionId): int { return $this->count; }
        public function save(Answer $answer): void {}
        public function delete(Answer $answer): void {}
    };
}

describe('ListAnswersHandler', function () {
    it('returns paginated answers', function () {
        $quiz = Quiz::create(title: 'PHP', slug: 'php', description: 'Quiz', type: QuizType::Quiz);
        $question = Question::create(type: QuestionType::SingleChoice, content: 'Q1', level: QuestionLevel::Easy, score: 1, position: 1, quiz: $quiz);
        $a1 = Answer::create(content: 'Answer 1', isCorrect: true, position: 1, question: $question);
        $a2 = Answer::create(content: 'Answer 2', isCorrect: false, position: 2, question: $question);

        $handler = new ListAnswersHandler(stubListAnswersRepo([$a1, $a2], 2));
        $result = $handler(new ListAnswersQuery(1, 20));

        expect($result)->toBeInstanceOf(AnswerListOutput::class);
        expect($result->pagination->items)->toHaveCount(2);
        expect($result->pagination->total)->toBe(2);
    });

    it('returns empty list when no answers', function () {
        $handler = new ListAnswersHandler(stubListAnswersRepo([], 0));
        $result = $handler(new ListAnswersQuery());

        expect($result->pagination->items)->toBeEmpty();
        expect($result->pagination->total)->toBe(0);
    });
});
