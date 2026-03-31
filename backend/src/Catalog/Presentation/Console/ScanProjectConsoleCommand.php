<?php

declare(strict_types=1);

namespace App\Catalog\Presentation\Console;

use App\Catalog\Application\Command\ScanProjectCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

#[AsCommand(name: 'app:scan-project', description: 'Scan a project for tech stacks and dependencies')]
final class ScanProjectConsoleCommand extends Command
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
        /** @var string $projectId */
        $projectId = $input->getArgument('projectId');
        $output->writeln(\sprintf('Scanning project %s...', $projectId));

        $envelope = $this->commandBus->dispatch(new ScanProjectCommand($projectId));

        $handled = $envelope->last(HandledStamp::class);
        if ($handled !== null) {
            $result = $handled->getResult();
            $output->writeln(\sprintf('Detected %d stack(s), %d dependency(ies).', $result->stacksDetected ?? 0, $result->dependenciesDetected ?? 0));
        }

        return Command::SUCCESS;
    }
}
