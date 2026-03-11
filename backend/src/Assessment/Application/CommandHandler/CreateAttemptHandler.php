<?php

declare(strict_types=1);

namespace App\Assessment\Application\CommandHandler;

use App\Assessment\Application\Command\CreateAttemptCommand;
use App\Assessment\Application\DTO\AttemptOutput;
use App\Assessment\Domain\Model\Attempt;
use App\Assessment\Domain\Repository\AttemptRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreateAttemptHandler
{
    public function __construct(
        private AttemptRepositoryInterface $attemptRepository,
    ) {
    }

    public function __invoke(CreateAttemptCommand $command): AttemptOutput
    {
        $input = $command->input;

        $attempt = Attempt::create(
            userId: $input->userId,
            quizId: $input->quizId,
        );

        $this->attemptRepository->save($attempt);

        return AttemptOutput::fromEntity($attempt);
    }
}
