<?php

declare(strict_types=1);

namespace App\Assessment\Application\CommandHandler;

use App\Assessment\Application\Command\DeleteAnswerCommand;
use App\Assessment\Domain\Repository\AnswerRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class DeleteAnswerHandler
{
    public function __construct(
        private AnswerRepositoryInterface $answerRepository,
    ) {
    }

    public function __invoke(DeleteAnswerCommand $command): void
    {
        $answer = $this->answerRepository->findById(Uuid::fromString($command->answerId));
        if ($answer === null) {
            throw NotFoundException::forEntity('Answer', $command->answerId);
        }

        $this->answerRepository->delete($answer);
    }
}
