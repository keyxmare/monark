<?php

declare(strict_types=1);

namespace App\Catalog\Application\CommandHandler;

use App\Catalog\Application\Command\DeleteFrameworkCommand;
use App\Catalog\Domain\Repository\FrameworkRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
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
