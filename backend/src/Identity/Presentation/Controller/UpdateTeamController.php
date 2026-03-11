<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Controller;

use App\Identity\Application\Command\UpdateTeamCommand;
use App\Identity\Application\DTO\UpdateTeamInput;
use App\Shared\Application\DTO\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/identity/teams/{id}', name: 'identity_teams_update', methods: ['PUT'])]
final readonly class UpdateTeamController
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke(string $id, #[MapRequestPayload] UpdateTeamInput $input): JsonResponse
    {
        $envelope = $this->commandBus->dispatch(new UpdateTeamCommand($id, $input));
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result)->toArray());
    }
}
