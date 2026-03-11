<?php

declare(strict_types=1);

namespace App\Catalog\Application\CommandHandler;

use App\Catalog\Application\Command\DeleteTechStackCommand;
use App\Catalog\Domain\Repository\TechStackRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class DeleteTechStackHandler
{
    public function __construct(
        private TechStackRepositoryInterface $techStackRepository,
    ) {
    }

    public function __invoke(DeleteTechStackCommand $command): void
    {
        $techStack = $this->techStackRepository->findById(Uuid::fromString($command->techStackId));
        if ($techStack === null) {
            throw NotFoundException::forEntity('TechStack', $command->techStackId);
        }

        $this->techStackRepository->delete($techStack);
    }
}
