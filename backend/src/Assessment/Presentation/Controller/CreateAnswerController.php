<?php

declare(strict_types=1);

namespace App\Assessment\Presentation\Controller;

use App\Assessment\Application\Command\CreateAnswerCommand;
use App\Assessment\Application\DTO\CreateAnswerInput;
use App\Shared\Application\DTO\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/assessment/answers', name: 'assessment_answers_create', methods: ['POST'])]
final readonly class CreateAnswerController
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke(#[MapRequestPayload] CreateAnswerInput $input): JsonResponse
    {
        $envelope = $this->commandBus->dispatch(new CreateAnswerCommand($input));
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result)->toArray(), 201);
    }
}
