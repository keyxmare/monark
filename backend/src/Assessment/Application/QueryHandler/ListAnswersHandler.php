<?php

declare(strict_types=1);

namespace App\Assessment\Application\QueryHandler;

use App\Assessment\Application\DTO\AnswerListOutput;
use App\Assessment\Application\DTO\AnswerOutput;
use App\Assessment\Application\Query\ListAnswersQuery;
use App\Assessment\Domain\Repository\AnswerRepositoryInterface;
use App\Shared\Application\DTO\PaginatedOutput;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListAnswersHandler
{
    public function __construct(
        private AnswerRepositoryInterface $answerRepository,
    ) {
    }

    public function __invoke(ListAnswersQuery $query): AnswerListOutput
    {
        if ($query->questionId !== null) {
            $questionUuid = Uuid::fromString($query->questionId);
            $answers = $this->answerRepository->findByQuestionId($questionUuid, $query->page, $query->perPage);
            $total = $this->answerRepository->countByQuestionId($questionUuid);
        } else {
            $answers = $this->answerRepository->findAll($query->page, $query->perPage);
            $total = $this->answerRepository->count();
        }

        $items = \array_map(
            static fn ($answer) => AnswerOutput::fromEntity($answer),
            $answers,
        );

        return new AnswerListOutput(
            pagination: new PaginatedOutput(
                items: $items,
                total: $total,
                page: $query->page,
                perPage: $query->perPage,
            ),
        );
    }
}
