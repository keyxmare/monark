<?php

declare(strict_types=1);

namespace App\Catalog\Presentation\Console;

use App\Catalog\Application\Service\TechStackVersionStatusUpdater;
use App\Catalog\Domain\Repository\TechStackRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:refresh-tech-stack-status', description: 'Recalculate maintenance status for all tech stacks from product_versions')]
final class RefreshTechStackStatusCommand extends Command
{
    public function __construct(
        private readonly TechStackRepositoryInterface $techStackRepository,
        private readonly TechStackVersionStatusUpdater $updater,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $stacks = $this->techStackRepository->findAll(1, 10000);
        $updated = $this->updater->refreshAll($stacks);

        $io->success(\sprintf('Updated %d tech stacks.', $updated));

        return Command::SUCCESS;
    }
}
