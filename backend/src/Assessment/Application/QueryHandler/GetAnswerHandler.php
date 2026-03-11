<?php

declare(strict_types=1);

namespace App\Assessment\Application\QueryHandler;

use App\Assessment\Application\DTO\AnswerOutput;
use App\Assessment\Application\Query\GetAnswerQuery;
use App\Assessment\Domain\Repository\AnswerRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetAnswerHandler
{
    public function __construct(
        private AnswerRepositoryInterface $answerRepository,
    ) {
    }

    public function __invoke(GetAnswerQuery $query): AnswerOutput
    {
        $answer = $this->answerRepository->findById(Uuid::fromString($query->answerId));
        if ($answer === null) {
            throw NotFoundException::forEntity('Answer', $query->answerId);
        }

        return AnswerOutput::fromEntity($answer);
    }
}
