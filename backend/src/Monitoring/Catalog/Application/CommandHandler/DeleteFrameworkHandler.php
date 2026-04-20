<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Application\CommandHandler;

use App\Hub\Shared\Domain\Exception\NotFoundException;
use App\Monitoring\Catalog\Application\Command\DeleteFrameworkCommand;
use App\Monitoring\Catalog\Domain\Repository\FrameworkRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class DeleteFrameworkHandler
{
    public function __construct(private FrameworkRepositoryInterface $frameworkRepository)
    {
    }

    public function __invoke(DeleteFrameworkCommand $command): void
    {
        $framework = $this->frameworkRepository->findById(Uuid::fromString($command->id));
        if ($framework === null) {
            throw NotFoundException::forEntity('Framework', $command->id);
        }

        $this->frameworkRepository->delete($framework);
    }
}
