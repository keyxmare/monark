<?php

declare(strict_types=1);

namespace App\Assessment\Application\CommandHandler;

use App\Assessment\Application\Command\DeleteQuizCommand;
use App\Assessment\Domain\Repository\QuizRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class DeleteQuizHandler
{
    public function __construct(
        private QuizRepositoryInterface $quizRepository,
    ) {
    }

    public function __invoke(DeleteQuizCommand $command): void
    {
        $quiz = $this->quizRepository->findById(Uuid::fromString($command->quizId));
        if ($quiz === null) {
            throw NotFoundException::forEntity('Quiz', $command->quizId);
        }

        $this->quizRepository->delete($quiz);
    }
}
