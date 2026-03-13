<?php

declare(strict_types=1);

namespace App\Catalog\Application\CommandHandler;

use App\Catalog\Application\Command\CreatePipelineCommand;
use App\Catalog\Application\DTO\PipelineOutput;
use App\Catalog\Domain\Model\Pipeline;
use App\Catalog\Domain\Repository\PipelineRepositoryInterface;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use DateTimeImmutable;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreatePipelineHandler
{
    public function __construct(
        private PipelineRepositoryInterface $pipelineRepository,
        private ProjectRepositoryInterface $projectRepository,
    ) {
    }

    public function __invoke(CreatePipelineCommand $command): PipelineOutput
    {
        $input = $command->input;

        $project = $this->projectRepository->findById(Uuid::fromString($input->projectId));
        if ($project === null) {
            throw NotFoundException::forEntity('Project', $input->projectId);
        }

        $pipeline = Pipeline::create(
            externalId: $input->externalId,
            ref: $input->ref,
            status: $input->status,
            duration: $input->duration,
            startedAt: new DateTimeImmutable($input->startedAt),
            finishedAt: $input->finishedAt !== null ? new DateTimeImmutable($input->finishedAt) : null,
            project: $project,
        );

        $this->pipelineRepository->save($pipeline);

        return PipelineOutput::fromEntity($pipeline);
    }
}
