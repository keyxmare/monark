<?php

declare(strict_types=1);

namespace App\Catalog\Presentation\Controller;

use App\Catalog\Application\Command\UpdateProviderCommand;
use App\Catalog\Application\DTO\UpdateProviderInput;
use App\Shared\Application\DTO\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/catalog/providers/{id}', name: 'catalog_providers_update', methods: ['PUT'])]
final readonly class UpdateProviderController
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke(string $id, #[MapRequestPayload] UpdateProviderInput $input): JsonResponse
    {
        $envelope = $this->commandBus->dispatch(new UpdateProviderCommand($id, $input));
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result)->toArray());
    }
}
