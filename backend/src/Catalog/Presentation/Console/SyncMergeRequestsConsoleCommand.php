<?php

declare(strict_types=1);

namespace App\Catalog\Presentation\Console;

use App\Catalog\Application\Command\SyncMergeRequestsCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

#[AsCommand(name: 'app:sync-merge-requests', description: 'Sync merge requests for a project')]
final class SyncMergeRequestsConsoleCommand extends Command
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('projectId', InputArgument::REQUIRED, 'The project UUID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectId = $input->getArgument('projectId');
        $output->writeln(\sprintf('Syncing merge requests for project %s...', $projectId));

        $this->commandBus->dispatch(new SyncMergeRequestsCommand($projectId));

        $output->writeln('Done.');

        return Command::SUCCESS;
    }
}
