<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Application\CommandHandler;

use App\Hub\Shared\Domain\Exception\NotFoundException;
use App\Monitoring\Catalog\Application\Command\CreateFrameworkCommand;
use App\Monitoring\Catalog\Application\DTO\FrameworkOutput;
use App\Monitoring\Catalog\Application\Mapper\FrameworkMapper;
use App\Monitoring\Catalog\Domain\Model\Framework;
use App\Monitoring\Catalog\Domain\Repository\FrameworkRepositoryInterface;
use App\Monitoring\Catalog\Domain\Repository\ProjectRepositoryInterface;
use DateTimeImmutable;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreateFrameworkHandler
{
    public function __construct(
        private FrameworkRepositoryInterface $frameworkRepository,
        private ProjectRepositoryInterface $projectRepository,
    ) {
    }

    public function __invoke(CreateFrameworkCommand $command): FrameworkOutput
    {
        $input = $command->input;

        $project = $this->projectRepository->findById(Uuid::fromString($input->projectId));
        if ($project === null) {
            throw NotFoundException::forEntity('Project', $input->projectId);
        }

        $framework = Framework::create(
            name: $input->name,
            version: $input->version,
            detectedAt: new DateTimeImmutable($input->detectedAt),
            languageName: $input->languageName,
            languageVersion: $input->languageVersion,
            project: $project,
        );

        $this->frameworkRepository->save($framework);

        return FrameworkMapper::toOutput($framework);
    }
}
