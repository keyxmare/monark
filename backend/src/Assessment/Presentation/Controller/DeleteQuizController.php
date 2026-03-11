<?php

declare(strict_types=1);

namespace App\Assessment\Presentation\Controller;

use App\Assessment\Application\Command\DeleteQuizCommand;
use App\Shared\Application\DTO\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/assessment/quizzes/{id}', name: 'assessment_quizzes_delete', methods: ['DELETE'])]
final readonly class DeleteQuizController
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        $this->commandBus->dispatch(new DeleteQuizCommand($id));

        return new JsonResponse(ApiResponse::success()->toArray(), 204);
    }
}
