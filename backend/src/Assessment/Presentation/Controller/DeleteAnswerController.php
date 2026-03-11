<?php

declare(strict_types=1);

namespace App\Assessment\Presentation\Controller;

use App\Assessment\Application\Command\DeleteAnswerCommand;
use App\Shared\Application\DTO\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/assessment/answers/{id}', name: 'assessment_answers_delete', methods: ['DELETE'])]
final readonly class DeleteAnswerController
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        $this->commandBus->dispatch(new DeleteAnswerCommand($id));

        return new JsonResponse(ApiResponse::success()->toArray(), 204);
    }
}
