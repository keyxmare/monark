<?php

declare(strict_types=1);

namespace App\Assessment\Application\CommandHandler;

use App\Assessment\Application\Command\DeleteQuestionCommand;
use App\Assessment\Domain\Repository\QuestionRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class DeleteQuestionHandler
{
    public function __construct(
        private QuestionRepositoryInterface $questionRepository,
    ) {
    }

    public function __invoke(DeleteQuestionCommand $command): void
    {
        $question = $this->questionRepository->findById(Uuid::fromString($command->questionId));
        if ($question === null) {
            throw NotFoundException::forEntity('Question', $command->questionId);
        }

        $this->questionRepository->delete($question);
    }
}
