<?php

declare(strict_types=1);

namespace App\Monitoring\Dependency\Application\CommandHandler;

use App\Monitoring\Dependency\Application\Command\SyncDependencyVersionsCommand;
use App\Monitoring\Dependency\Application\Command\SyncSingleDependencyVersionCommand;
use App\Monitoring\Dependency\Domain\Repository\DependencyRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class SyncDependencyVersionsHandler
{
    public function __construct(
        private DependencyRepositoryInterface $dependencyRepository,
        private MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke(SyncDependencyVersionsCommand $command): int
    {
        $packages = $this->dependencyRepository->findUniquePackages();

        if ($command->packageNames !== null) {
            $allowed = \array_flip($command->packageNames);
            $packages = \array_values(\array_filter(
                $packages,
                static fn (array $p) => isset($allowed[$p['name']]),
            ));
        }

        $total = \count($packages);
        $index = 0;

        foreach ($packages as $pkg) {
            $manager = $pkg['packageManager']->value;

            $index++;

            $this->commandBus->dispatch(
                new SyncSingleDependencyVersionCommand(
                    packageName: $pkg['name'],
                    packageManager: $manager,
                    syncId: $command->syncId,
                    index: $index,
                    total: $total,
                ),
                [new DispatchAfterCurrentBusStamp()],
            );
        }

        return $total;
    }
}
