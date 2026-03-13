<?php

declare(strict_types=1);

namespace App\Catalog\Application\CommandHandler;

use App\Catalog\Application\Command\ScanProjectCommand;
use App\Catalog\Application\DTO\ScanResultOutput;
use App\Shared\Domain\Event\ProjectScannedEvent;
use App\Catalog\Domain\Model\TechStack;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Catalog\Domain\Repository\TechStackRepositoryInterface;
use App\Catalog\Domain\Port\ProjectScannerInterface;
use App\Shared\Domain\Exception\NotFoundException;
use App\Shared\Domain\Port\DependencyWriterPort;
use DateTimeImmutable;
use DomainException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class ScanProjectHandler
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
        private TechStackRepositoryInterface $techStackRepository,
        private DependencyWriterPort $dependencyWriter,
        private ProjectScannerInterface $projectScanner,
        private MessageBusInterface $eventBus,
    ) {
    }

    public function __invoke(ScanProjectCommand $command): ScanResultOutput
    {
        $project = $this->projectRepository->findById(Uuid::fromString($command->projectId));
        if ($project === null) {
            throw NotFoundException::forEntity('Project', $command->projectId);
        }

        if ($project->getProvider() === null || $project->getExternalId() === null) {
            throw new DomainException(\sprintf('Project "%s" is not linked to a provider.', $command->projectId));
        }

        $scanResult = $this->projectScanner->scan($project);

        $projectId = $project->getId();
        $this->techStackRepository->deleteByProjectId($projectId);
        $this->dependencyWriter->deleteByProjectId($projectId);

        $stackOutputs = [];
        foreach ($scanResult->stacks as $detected) {
            $techStack = TechStack::create(
                language: $detected->language,
                framework: $detected->framework,
                version: $detected->version,
                frameworkVersion: $detected->frameworkVersion,
                detectedAt: new DateTimeImmutable(),
                project: $project,
            );
            $this->techStackRepository->save($techStack);
            $stackOutputs[] = [
                'language' => $detected->language,
                'framework' => $detected->framework,
                'version' => $detected->version,
                'frameworkVersion' => $detected->frameworkVersion,
            ];
        }

        $depOutputs = [];
        foreach ($scanResult->dependencies as $detected) {
            $this->dependencyWriter->createFromScan(
                name: $detected->name,
                currentVersion: $detected->currentVersion,
                packageManager: $detected->packageManager->value,
                type: $detected->type->value,
                projectId: $project->getId(),
                repositoryUrl: $detected->repositoryUrl,
            );
            $depOutputs[] = [
                'name' => $detected->name,
                'version' => $detected->currentVersion,
                'packageManager' => $detected->packageManager->value,
                'type' => $detected->type->value,
            ];
        }

        $this->eventBus->dispatch(new ProjectScannedEvent(
            projectId: $command->projectId,
            scanResult: $scanResult,
        ));

        return new ScanResultOutput(
            stacksDetected: \count($stackOutputs),
            dependenciesDetected: \count($depOutputs),
            stacks: $stackOutputs,
            dependencies: $depOutputs,
        );
    }
}
