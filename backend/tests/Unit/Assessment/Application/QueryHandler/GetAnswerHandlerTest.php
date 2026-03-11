<?php

declare(strict_types=1);

use App\Assessment\Application\DTO\AnswerOutput;
use App\Assessment\Application\Query\GetAnswerQuery;
use App\Assessment\Application\QueryHandler\GetAnswerHandler;
use App\Assessment\Domain\Model\Answer;
use App\Assessment\Domain\Model\Question;
use App\Assessment\Domain\Model\QuestionLevel;
use App\Assessment\Domain\Model\QuestionType;
use App\Assessment\Domain\Model\Quiz;
use App\Assessment\Domain\Model\QuizType;
use App\Assessment\Domain\Repository\AnswerRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Uid\Uuid;

function stubGetAnswerRepo(?Answer $answer = null): AnswerRepositoryInterface
{
    return new class ($answer) implements AnswerRepositoryInterface {
        public function __construct(private readonly ?Answer $answer) {}
        public function findById(Uuid $id): ?Answer { return $this->answer; }
        public function findAll(int $page = 1, int $perPage = 20): array { return []; }
        public function findByQuestionId(Uuid $questionId, int $page = 1, int $perPage = 20): array { return []; }
        public function count(): int { return 0; }
        public function countByQuestionId(Uuid $questionId): int { return 0; }
        public function save(Answer $answer): void {}
        public function delete(Answer $answer): void {}
    };
}

describe('GetAnswerHandler', function () {
    it('returns an answer by id', function () {
        $quiz = Quiz::create(title: 'PHP', slug: 'php', description: 'Quiz', type: QuizType::Quiz);
        $question = Question::create(type: QuestionType::SingleChoice, content: 'Q1', level: QuestionLevel::Easy, score: 1, position: 1, quiz: $quiz);
        $answer = Answer::create(content: 'A programming language', isCorrect: true, position: 1, question: $question);

        $handler = new GetAnswerHandler(stubGetAnswerRepo($answer));
        $result = $handler(new GetAnswerQuery($answer->getId()->toRfc4122()));

        expect($result)->toBeInstanceOf(AnswerOutput::class);
        expect($result->content)->toBe('A programming language');
        expect($result->isCorrect)->toBeTrue();
    });

    it('throws not found when answer does not exist', function () {
        $handler = new GetAnswerHandler(stubGetAnswerRepo(null));
        $handler(new GetAnswerQuery('00000000-0000-0000-0000-000000000000'));
    })->throws(NotFoundException::class);
});
