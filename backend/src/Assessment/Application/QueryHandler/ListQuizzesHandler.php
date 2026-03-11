<?php

declare(strict_types=1);

namespace App\Assessment\Application\QueryHandler;

use App\Assessment\Application\DTO\QuizListOutput;
use App\Assessment\Application\DTO\QuizOutput;
use App\Assessment\Application\Query\ListQuizzesQuery;
use App\Assessment\Domain\Repository\QuizRepositoryInterface;
use App\Shared\Application\DTO\PaginatedOutput;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListQuizzesHandler
{
    public function __construct(
        private QuizRepositoryInterface $quizRepository,
    ) {
    }

    public function __invoke(ListQuizzesQuery $query): QuizListOutput
    {
        $quizzes = $this->quizRepository->findAll($query->page, $query->perPage);
        $total = $this->quizRepository->count();

        $items = \array_map(
            static fn ($quiz) => QuizOutput::fromEntity($quiz),
            $quizzes,
        );

        return new QuizListOutput(
            pagination: new PaginatedOutput(
                items: $items,
                total: $total,
                page: $query->page,
                perPage: $query->perPage,
            ),
        );
    }
}
