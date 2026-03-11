<?php

declare(strict_types=1);

namespace App\Assessment\Application\CommandHandler;

use App\Assessment\Application\Command\UpdateAnswerCommand;
use App\Assessment\Application\DTO\AnswerOutput;
use App\Assessment\Domain\Repository\AnswerRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class UpdateAnswerHandler
{
    public function __construct(
        private AnswerRepositoryInterface $answerRepository,
    ) {
    }

    public function __invoke(UpdateAnswerCommand $command): AnswerOutput
    {
        $answer = $this->answerRepository->findById(Uuid::fromString($command->answerId));
        if ($answer === null) {
            throw NotFoundException::forEntity('Answer', $command->answerId);
        }

        $input = $command->input;

        $answer->update(
            content: $input->content,
            isCorrect: $input->isCorrect,
            position: $input->position,
        );

        $this->answerRepository->save($answer);

        return AnswerOutput::fromEntity($answer);
    }
}
