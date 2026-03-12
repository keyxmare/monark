<?php

declare(strict_types=1);

namespace App\Assessment\Presentation\Controller;

use App\Assessment\Application\DTO\QuizListOutput;
use App\Assessment\Application\Query\ListQuizzesQuery;
use App\Shared\Application\DTO\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/assessment/quizzes', name: 'assessment_quizzes_list', methods: ['GET'])]
final readonly class ListQuizzesController
{
    public function __construct(
        private MessageBusInterface $queryBus,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $perPage = $request->query->getInt('per_page', 20);

        $envelope = $this->queryBus->dispatch(new ListQuizzesQuery($page, $perPage));
        /** @var QuizListOutput $result */
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result->pagination->toArray())->toArray());
    }
}
