<?php

declare(strict_types=1);

use App\Assessment\Application\Command\UpdateQuestionCommand;
use App\Assessment\Application\CommandHandler\UpdateQuestionHandler;
use App\Assessment\Application\DTO\QuestionOutput;
use App\Assessment\Application\DTO\UpdateQuestionInput;
use App\Assessment\Domain\Model\Question;
use App\Assessment\Domain\Model\QuestionLevel;
use App\Assessment\Domain\Model\QuestionType;
use App\Assessment\Domain\Model\Quiz;
use App\Assessment\Domain\Model\QuizType;
use App\Assessment\Domain\Repository\QuestionRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Uid\Uuid;

function stubUpdateQuestionRepo(?Question $question = null): QuestionRepositoryInterface
{
    return new class ($question) implements QuestionRepositoryInterface {
        public ?Question $saved = null;
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
            $this->saved = $question;
        }
        public function delete(Question $question): void
        {
        }
    };
}

describe('UpdateQuestionHandler', function () {
    it('updates a question successfully', function () {
        $quiz = Quiz::create(title: 'PHP', slug: 'php', description: 'Quiz', type: QuizType::Quiz);
        $question = Question::create(
            type: QuestionType::SingleChoice,
            content: 'What is PHP?',
            level: QuestionLevel::Easy,
            score: 1,
            position: 1,
            quiz: $quiz,
        );

        $repo = \stubUpdateQuestionRepo($question);
        $handler = new UpdateQuestionHandler($repo);

        $input = new UpdateQuestionInput(content: 'What is PHP 8.4?', score: 2);
        $result = $handler(new UpdateQuestionCommand($question->getId()->toRfc4122(), $input));

        expect($result)->toBeInstanceOf(QuestionOutput::class);
        expect($result->content)->toBe('What is PHP 8.4?');
        expect($result->score)->toBe(2);
        expect($repo->saved)->not->toBeNull();
    });

    it('throws not found when question does not exist', function () {
        $handler = new UpdateQuestionHandler(\stubUpdateQuestionRepo(null));
        $input = new UpdateQuestionInput(content: 'Updated');
        $handler(new UpdateQuestionCommand('00000000-0000-0000-0000-000000000000', $input));
    })->throws(NotFoundException::class);
});
