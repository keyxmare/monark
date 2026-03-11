<?php

declare(strict_types=1);

namespace App\Assessment\Application\QueryHandler;

use App\Assessment\Application\DTO\QuizOutput;
use App\Assessment\Application\Query\GetQuizQuery;
use App\Assessment\Domain\Repository\QuizRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetQuizHandler
{
    public function __construct(
        private QuizRepositoryInterface $quizRepository,
    ) {
    }

    public function __invoke(GetQuizQuery $query): QuizOutput
    {
        $quiz = $this->quizRepository->findById(Uuid::fromString($query->quizId));
        if ($quiz === null) {
            throw NotFoundException::forEntity('Quiz', $query->quizId);
        }

        return QuizOutput::fromEntity($quiz);
    }
}
