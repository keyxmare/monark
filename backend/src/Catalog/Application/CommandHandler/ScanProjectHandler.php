<?php

declare(strict_types=1);

namespace App\Catalog\Application\CommandHandler;

use App\Catalog\Application\Command\ScanProjectCommand;
use App\Catalog\Application\DTO\ScanResultOutput;
use App\Catalog\Domain\Model\Framework;
use App\Catalog\Domain\Model\Language;
use App\Catalog\Domain\Port\ProjectScannerInterface;
use App\Catalog\Domain\Repository\FrameworkRepositoryInterface;
use App\Catalog\Domain\Repository\LanguageRepositoryInterface;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\Domain\Event\ProjectScannedEvent;
use App\Shared\Domain\Exception\NotFoundException;
use App\Shared\Domain\Port\DependencyWriterPort;
use DateTimeImmutable;
use DomainException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
readonly class ScanProjectHandler
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
        private LanguageRepositoryInterface $languageRepository,
        private FrameworkRepositoryInterface $frameworkRepository,
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

        if ($scanResult->stacks === [] && $scanResult->dependencies === []) {
            $this->eventBus->dispatch(new ProjectScannedEvent(
                projectId: $command->projectId,
                scanResult: $scanResult,
            ));

            return new ScanResultOutput(
                stacksDetected: 0,
                dependenciesDetected: 0,
                stacks: [],
                dependencies: [],
            );
        }

        $projectId = $project->getId();
        $this->languageRepository->deleteByProjectId($projectId);
        $this->frameworkRepository->deleteByProjectId($projectId);

        $stackOutputs = [];
        foreach ($scanResult->stacks as $detected) {
            if ($detected->framework === 'none') {
                continue;
            }

            $language = Language::create(
                name: $detected->language,
                version: $detected->version,
                detectedAt: new DateTimeImmutable(),
                project: $project,
            );
            $this->languageRepository->save($language);

            $framework = Framework::create(
                name: $detected->framework,
                version: $detected->frameworkVersion,
                detectedAt: new DateTimeImmutable(),
                language: $language,
                project: $project,
            );
            $this->frameworkRepository->save($framework);

            $stackOutputs[] = [
                'language' => $detected->language,
                'framework' => $detected->framework,
                'version' => $detected->version,
                'frameworkVersion' => $detected->frameworkVersion,
            ];
        }

        $depOutputs = [];
        $scannedDeps = [];
        foreach ($scanResult->dependencies as $detected) {
            $this->dependencyWriter->upsertFromScan(
                name: $detected->name,
                currentVersion: $detected->currentVersion,
                packageManager: $detected->packageManager->value,
                type: $detected->type->value,
                projectId: $project->getId(),
                repositoryUrl: $detected->repositoryUrl,
            );
            $scannedDeps[] = [
                'name' => $detected->name,
                'packageManager' => $detected->packageManager->value,
            ];
            $depOutputs[] = [
                'name' => $detected->name,
                'version' => $detected->currentVersion,
                'packageManager' => $detected->packageManager->value,
                'type' => $detected->type->value,
            ];
        }

        $this->dependencyWriter->removeStaleByProjectId($projectId, $scannedDeps);

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
