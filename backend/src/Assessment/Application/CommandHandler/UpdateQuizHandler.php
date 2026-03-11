<?php

declare(strict_types=1);

namespace App\Assessment\Application\CommandHandler;

use App\Assessment\Application\Command\UpdateQuizCommand;
use App\Assessment\Application\DTO\QuizOutput;
use App\Assessment\Domain\Model\QuizStatus;
use App\Assessment\Domain\Model\QuizType;
use App\Assessment\Domain\Repository\QuizRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class UpdateQuizHandler
{
    public function __construct(
        private QuizRepositoryInterface $quizRepository,
    ) {
    }

    public function __invoke(UpdateQuizCommand $command): QuizOutput
    {
        $quiz = $this->quizRepository->findById(Uuid::fromString($command->quizId));
        if ($quiz === null) {
            throw NotFoundException::forEntity('Quiz', $command->quizId);
        }

        $input = $command->input;

        $quiz->update(
            title: $input->title,
            slug: $input->slug,
            description: $input->description,
            type: $input->type !== null ? QuizType::from($input->type) : null,
            status: $input->status !== null ? QuizStatus::from($input->status) : null,
            startsAt: $input->startsAt !== null ? new \DateTimeImmutable($input->startsAt) : null,
            endsAt: $input->endsAt !== null ? new \DateTimeImmutable($input->endsAt) : null,
            timeLimit: $input->timeLimit,
        );

        $this->quizRepository->save($quiz);

        return QuizOutput::fromEntity($quiz);
    }
}
