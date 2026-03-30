<?php

declare(strict_types=1);

namespace App\Catalog\Application\CommandHandler;

use App\Catalog\Application\Command\CreateFrameworkCommand;
use App\Catalog\Application\DTO\FrameworkOutput;
use App\Catalog\Application\Mapper\FrameworkMapper;
use App\Catalog\Domain\Model\Framework;
use App\Catalog\Domain\Repository\FrameworkRepositoryInterface;
use App\Catalog\Domain\Repository\LanguageRepositoryInterface;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use DateTimeImmutable;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreateFrameworkHandler
{
    public function __construct(
        private FrameworkRepositoryInterface $frameworkRepository,
        private LanguageRepositoryInterface $languageRepository,
        private ProjectRepositoryInterface $projectRepository,
    ) {
    }

    public function __invoke(CreateFrameworkCommand $command): FrameworkOutput
    {
        $input = $command->input;

        $language = $this->languageRepository->findById(Uuid::fromString($input->languageId));
        if ($language === null) {
            throw NotFoundException::forEntity('Language', $input->languageId);
        }

        $project = $this->projectRepository->findById(Uuid::fromString($input->projectId));
        if ($project === null) {
            throw NotFoundException::forEntity('Project', $input->projectId);
        }

        $framework = Framework::create(
            name: $input->name,
            version: $input->version,
            detectedAt: new DateTimeImmutable($input->detectedAt),
            language: $language,
            project: $project,
        );

        $this->frameworkRepository->save($framework);

        return FrameworkMapper::toOutput($framework);
    }
}
