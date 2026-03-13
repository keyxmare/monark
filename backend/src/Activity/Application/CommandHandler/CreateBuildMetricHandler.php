<?php

declare(strict_types=1);

namespace App\Activity\Application\CommandHandler;

use App\Activity\Application\Command\CreateBuildMetricCommand;
use App\Activity\Application\DTO\BuildMetricOutput;
use App\Activity\Domain\Model\BuildMetric;
use App\Activity\Domain\Repository\BuildMetricRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreateBuildMetricHandler
{
    public function __construct(
        private BuildMetricRepositoryInterface $buildMetricRepository,
    ) {
    }

    public function __invoke(CreateBuildMetricCommand $command): BuildMetricOutput
    {
        $buildMetric = BuildMetric::create(
            projectId: Uuid::fromString($command->projectId),
            commitSha: $command->input->commitSha,
            ref: $command->input->ref,
            backendCoverage: $command->input->backendCoverage,
            frontendCoverage: $command->input->frontendCoverage,
            mutationScore: $command->input->mutationScore,
        );

        $this->buildMetricRepository->save($buildMetric);

        return BuildMetricOutput::fromEntity($buildMetric);
    }
}
