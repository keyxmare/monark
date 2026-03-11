<?php

declare(strict_types=1);

namespace App\Assessment\Application\CommandHandler;

use App\Assessment\Application\Command\CreateQuestionCommand;
use App\Assessment\Application\DTO\QuestionOutput;
use App\Assessment\Domain\Model\Question;
use App\Assessment\Domain\Model\QuestionLevel;
use App\Assessment\Domain\Model\QuestionType;
use App\Assessment\Domain\Repository\QuestionRepositoryInterface;
use App\Assessment\Domain\Repository\QuizRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreateQuestionHandler
{
    public function __construct(
        private QuestionRepositoryInterface $questionRepository,
        private QuizRepositoryInterface $quizRepository,
    ) {
    }

    public function __invoke(CreateQuestionCommand $command): QuestionOutput
    {
        $input = $command->input;

        $quiz = $this->quizRepository->findById(Uuid::fromString($input->quizId));
        if ($quiz === null) {
            throw NotFoundException::forEntity('Quiz', $input->quizId);
        }

        $question = Question::create(
            type: QuestionType::from($input->type),
            content: $input->content,
            level: QuestionLevel::from($input->level),
            score: $input->score,
            position: $input->position,
            quiz: $quiz,
        );

        $this->questionRepository->save($question);

        return QuestionOutput::fromEntity($question);
    }
}
