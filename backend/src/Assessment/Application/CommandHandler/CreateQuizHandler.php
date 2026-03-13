<?php

declare(strict_types=1);

namespace App\Assessment\Application\CommandHandler;

use App\Assessment\Application\Command\CreateQuizCommand;
use App\Assessment\Application\DTO\QuizOutput;
use App\Assessment\Domain\Model\Quiz;
use App\Assessment\Domain\Model\QuizStatus;
use App\Assessment\Domain\Model\QuizType;
use App\Assessment\Domain\Repository\QuizRepositoryInterface;
use App\Shared\Domain\Exception\DomainException;
use DateTimeImmutable;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreateQuizHandler
{
    public function __construct(
        private QuizRepositoryInterface $quizRepository,
    ) {
    }

    public function __invoke(CreateQuizCommand $command): QuizOutput
    {
        $input = $command->input;

        $existing = $this->quizRepository->findBySlug($input->slug);
        if ($existing !== null) {
            throw new class ('A quiz with this slug already exists.') extends DomainException {};
        }

        $quiz = Quiz::create(
            title: $input->title,
            slug: $input->slug,
            description: $input->description,
            type: QuizType::from($input->type),
            status: QuizStatus::from($input->status),
            startsAt: $input->startsAt !== null ? new DateTimeImmutable($input->startsAt) : null,
            endsAt: $input->endsAt !== null ? new DateTimeImmutable($input->endsAt) : null,
            timeLimit: $input->timeLimit,
            authorId: $input->authorId,
        );

        $this->quizRepository->save($quiz);

        return QuizOutput::fromEntity($quiz);
    }
}
