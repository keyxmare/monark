<?php

declare(strict_types=1);

use App\Assessment\Application\Command\DeleteQuestionCommand;
use App\Assessment\Application\CommandHandler\DeleteQuestionHandler;
use App\Assessment\Domain\Model\Question;
use App\Assessment\Domain\Model\QuestionLevel;
use App\Assessment\Domain\Model\QuestionType;
use App\Assessment\Domain\Model\Quiz;
use App\Assessment\Domain\Model\QuizType;
use App\Assessment\Domain\Repository\QuestionRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Uid\Uuid;

function stubDeleteQuestionRepo(?Question $question = null): QuestionRepositoryInterface
{
    return new class ($question) implements QuestionRepositoryInterface {
        public bool $deleted = false;
        public function __construct(private readonly ?Question $question) {}
        public function findById(Uuid $id): ?Question { return $this->question; }
        public function findAll(int $page = 1, int $perPage = 20): array { return []; }
        public function findByQuizId(Uuid $quizId, int $page = 1, int $perPage = 20): array { return []; }
        public function count(): int { return 0; }
        public function countByQuizId(Uuid $quizId): int { return 0; }
        public function save(Question $question): void {}
        public function delete(Question $question): void { $this->deleted = true; }
    };
}

describe('DeleteQuestionHandler', function () {
    it('deletes a question successfully', function () {
        $quiz = Quiz::create(title: 'PHP', slug: 'php', description: 'Quiz', type: QuizType::Quiz);
        $question = Question::create(
            type: QuestionType::SingleChoice,
            content: 'What is PHP?',
            level: QuestionLevel::Easy,
            score: 1,
            position: 1,
            quiz: $quiz,
        );

        $repo = stubDeleteQuestionRepo($question);
        $handler = new DeleteQuestionHandler($repo);

        $handler(new DeleteQuestionCommand($question->getId()->toRfc4122()));

        expect($repo->deleted)->toBeTrue();
    });

    it('throws not found when question does not exist', function () {
        $handler = new DeleteQuestionHandler(stubDeleteQuestionRepo(null));
        $handler(new DeleteQuestionCommand('00000000-0000-0000-0000-000000000000'));
    })->throws(NotFoundException::class);
});
