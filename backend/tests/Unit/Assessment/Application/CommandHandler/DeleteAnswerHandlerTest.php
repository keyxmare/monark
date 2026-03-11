<?php

declare(strict_types=1);

use App\Assessment\Application\Command\DeleteAnswerCommand;
use App\Assessment\Application\CommandHandler\DeleteAnswerHandler;
use App\Assessment\Domain\Model\Answer;
use App\Assessment\Domain\Model\Question;
use App\Assessment\Domain\Model\QuestionLevel;
use App\Assessment\Domain\Model\QuestionType;
use App\Assessment\Domain\Model\Quiz;
use App\Assessment\Domain\Model\QuizType;
use App\Assessment\Domain\Repository\AnswerRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Uid\Uuid;

function stubDeleteAnswerRepo(?Answer $answer = null): AnswerRepositoryInterface
{
    return new class ($answer) implements AnswerRepositoryInterface {
        public bool $deleted = false;
        public function __construct(private readonly ?Answer $answer) {}
        public function findById(Uuid $id): ?Answer { return $this->answer; }
        public function findAll(int $page = 1, int $perPage = 20): array { return []; }
        public function findByQuestionId(Uuid $questionId, int $page = 1, int $perPage = 20): array { return []; }
        public function count(): int { return 0; }
        public function countByQuestionId(Uuid $questionId): int { return 0; }
        public function save(Answer $answer): void {}
        public function delete(Answer $answer): void { $this->deleted = true; }
    };
}

describe('DeleteAnswerHandler', function () {
    it('deletes an answer successfully', function () {
        $quiz = Quiz::create(title: 'PHP', slug: 'php', description: 'Quiz', type: QuizType::Quiz);
        $question = Question::create(
            type: QuestionType::SingleChoice,
            content: 'What is PHP?',
            level: QuestionLevel::Easy,
            score: 1,
            position: 1,
            quiz: $quiz,
        );
        $answer = Answer::create(content: 'A language', isCorrect: true, position: 1, question: $question);

        $repo = stubDeleteAnswerRepo($answer);
        $handler = new DeleteAnswerHandler($repo);

        $handler(new DeleteAnswerCommand($answer->getId()->toRfc4122()));

        expect($repo->deleted)->toBeTrue();
    });

    it('throws not found when answer does not exist', function () {
        $handler = new DeleteAnswerHandler(stubDeleteAnswerRepo(null));
        $handler(new DeleteAnswerCommand('00000000-0000-0000-0000-000000000000'));
    })->throws(NotFoundException::class);
});
