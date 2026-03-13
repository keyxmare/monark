<?php

declare(strict_types=1);

use App\Assessment\Application\Command\UpdateAnswerCommand;
use App\Assessment\Application\CommandHandler\UpdateAnswerHandler;
use App\Assessment\Application\DTO\AnswerOutput;
use App\Assessment\Application\DTO\UpdateAnswerInput;
use App\Assessment\Domain\Model\Answer;
use App\Assessment\Domain\Model\Question;
use App\Assessment\Domain\Model\QuestionLevel;
use App\Assessment\Domain\Model\QuestionType;
use App\Assessment\Domain\Model\Quiz;
use App\Assessment\Domain\Model\QuizType;
use App\Assessment\Domain\Repository\AnswerRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Uid\Uuid;

function stubUpdateAnswerRepo(?Answer $answer = null): AnswerRepositoryInterface
{
    return new class ($answer) implements AnswerRepositoryInterface {
        public ?Answer $saved = null;
        public function __construct(private readonly ?Answer $answer)
        {
        }
        public function findById(Uuid $id): ?Answer
        {
            return $this->answer;
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

describe('UpdateAnswerHandler', function () {
    it('updates an answer successfully', function () {
        $quiz = Quiz::create(title: 'PHP', slug: 'php', description: 'Quiz', type: QuizType::Quiz);
        $question = Question::create(
            type: QuestionType::SingleChoice,
            content: 'What is PHP?',
            level: QuestionLevel::Easy,
            score: 1,
            position: 1,
            quiz: $quiz,
        );
        $answer = Answer::create(content: 'A language', isCorrect: false, position: 1, question: $question);

        $repo = \stubUpdateAnswerRepo($answer);
        $handler = new UpdateAnswerHandler($repo);

        $input = new UpdateAnswerInput(content: 'A programming language', isCorrect: true);
        $result = $handler(new UpdateAnswerCommand($answer->getId()->toRfc4122(), $input));

        expect($result)->toBeInstanceOf(AnswerOutput::class);
        expect($result->content)->toBe('A programming language');
        expect($result->isCorrect)->toBeTrue();
        expect($repo->saved)->not->toBeNull();
    });

    it('throws not found when answer does not exist', function () {
        $handler = new UpdateAnswerHandler(\stubUpdateAnswerRepo(null));
        $input = new UpdateAnswerInput(content: 'Updated');
        $handler(new UpdateAnswerCommand('00000000-0000-0000-0000-000000000000', $input));
    })->throws(NotFoundException::class);
});
