<?php

declare(strict_types=1);

namespace App\Assessment\Application\QueryHandler;

use App\Assessment\Application\DTO\QuestionOutput;
use App\Assessment\Application\Query\GetQuestionQuery;
use App\Assessment\Domain\Repository\QuestionRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetQuestionHandler
{
    public function __construct(
        private QuestionRepositoryInterface $questionRepository,
    ) {
    }

    public function __invoke(GetQuestionQuery $query): QuestionOutput
    {
        $question = $this->questionRepository->findById(Uuid::fromString($query->questionId));
        if ($question === null) {
            throw NotFoundException::forEntity('Question', $query->questionId);
        }

        return QuestionOutput::fromEntity($question);
    }
}
