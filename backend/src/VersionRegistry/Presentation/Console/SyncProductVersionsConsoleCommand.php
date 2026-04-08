<?php

declare(strict_types=1);

namespace App\VersionRegistry\Presentation\Console;

use App\VersionRegistry\Application\Command\SyncProductVersionsCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(name: 'app:sync-product-versions', description: 'Sync product versions from external sources')]
final class SyncProductVersionsConsoleCommand extends Command
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->commandBus->dispatch(new SyncProductVersionsCommand());
        $output->writeln('Product version sync dispatched.');

        return Command::SUCCESS;
    }
}
