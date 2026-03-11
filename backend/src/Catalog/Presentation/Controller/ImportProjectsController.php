<?php

declare(strict_types=1);

namespace App\Catalog\Presentation\Controller;

use App\Catalog\Application\Command\ImportProjectsCommand;
use App\Catalog\Application\DTO\ImportProjectsInput;
use App\Identity\Domain\Model\User;
use App\Shared\Application\DTO\ApiResponse;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/catalog/providers/{id}/import', name: 'catalog_providers_import', methods: ['POST'])]
final readonly class ImportProjectsController
{
    public function __construct(
        private MessageBusInterface $commandBus,
        private Security $security,
    ) {
    }

    public function __invoke(string $id, #[MapRequestPayload] ImportProjectsInput $input): JsonResponse
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $envelope = $this->commandBus->dispatch(
            new ImportProjectsCommand($id, $input, $user->getId()->toRfc4122()),
        );
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result)->toArray(), 201);
    }
}
