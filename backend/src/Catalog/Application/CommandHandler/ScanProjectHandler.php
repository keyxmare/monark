<?php

declare(strict_types=1);

namespace App\Catalog\Application\CommandHandler;

use App\Catalog\Application\Command\ScanProjectCommand;
use App\Catalog\Application\DTO\ScanResultOutput;
use App\Catalog\Domain\Model\TechStack;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Catalog\Domain\Repository\TechStackRepositoryInterface;
use App\Catalog\Infrastructure\Scanner\ProjectScanner;
use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class ScanProjectHandler
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
        private TechStackRepositoryInterface $techStackRepository,
        private DependencyRepositoryInterface $dependencyRepository,
        private ProjectScanner $projectScanner,
    ) {
    }

    public function __invoke(ScanProjectCommand $command): ScanResultOutput
    {
        $project = $this->projectRepository->findById(Uuid::fromString($command->projectId));
        if ($project === null) {
            throw NotFoundException::forEntity('Project', $command->projectId);
        }

        if ($project->getProvider() === null || $project->getExternalId() === null) {
            throw new \DomainException(\sprintf('Project "%s" is not linked to a provider.', $command->projectId));
        }

        $scanResult = $this->projectScanner->scan($project);

        $projectId = $project->getId();
        $this->techStackRepository->deleteByProjectId($projectId);
        $this->dependencyRepository->deleteByProjectId($projectId);

        $stackOutputs = [];
        foreach ($scanResult->stacks as $detected) {
            $techStack = TechStack::create(
                language: $detected->language,
                framework: $detected->framework,
                version: $detected->version,
                frameworkVersion: $detected->frameworkVersion,
                detectedAt: new \DateTimeImmutable(),
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
            $dependency = Dependency::create(
                name: $detected->name,
                currentVersion: $detected->currentVersion,
                latestVersion: $detected->currentVersion,
                ltsVersion: $detected->currentVersion,
                packageManager: $detected->packageManager,
                type: $detected->type,
                isOutdated: false,
                project: $project,
                repositoryUrl: $detected->repositoryUrl,
            );
            $this->dependencyRepository->save($dependency);
            $depOutputs[] = [
                'name' => $detected->name,
                'version' => $detected->currentVersion,
                'packageManager' => $detected->packageManager->value,
                'type' => $detected->type->value,
            ];
        }

        return new ScanResultOutput(
            stacksDetected: \count($stackOutputs),
            dependenciesDetected: \count($depOutputs),
            stacks: $stackOutputs,
            dependencies: $depOutputs,
        );
    }
}
