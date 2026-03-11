<?php

declare(strict_types=1);

namespace App\Assessment\Application\QueryHandler;

use App\Assessment\Application\DTO\QuestionListOutput;
use App\Assessment\Application\DTO\QuestionOutput;
use App\Assessment\Application\Query\ListQuestionsQuery;
use App\Assessment\Domain\Repository\QuestionRepositoryInterface;
use App\Shared\Application\DTO\PaginatedOutput;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListQuestionsHandler
{
    public function __construct(
        private QuestionRepositoryInterface $questionRepository,
    ) {
    }

    public function __invoke(ListQuestionsQuery $query): QuestionListOutput
    {
        if ($query->quizId !== null) {
            $quizUuid = Uuid::fromString($query->quizId);
            $questions = $this->questionRepository->findByQuizId($quizUuid, $query->page, $query->perPage);
            $total = $this->questionRepository->countByQuizId($quizUuid);
        } else {
            $questions = $this->questionRepository->findAll($query->page, $query->perPage);
            $total = $this->questionRepository->count();
        }

        $items = \array_map(
            static fn ($question) => QuestionOutput::fromEntity($question),
            $questions,
        );

        return new QuestionListOutput(
            pagination: new PaginatedOutput(
                items: $items,
                total: $total,
                page: $query->page,
                perPage: $query->perPage,
            ),
        );
    }
}
