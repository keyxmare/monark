<?php

declare(strict_types=1);

namespace App\Catalog\Application\CommandHandler;

use App\Catalog\Application\Command\CreateLanguageCommand;
use App\Catalog\Application\DTO\LanguageOutput;
use App\Catalog\Application\Mapper\LanguageMapper;
use App\Catalog\Domain\Model\Language;
use App\Catalog\Domain\Repository\LanguageRepositoryInterface;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use DateTimeImmutable;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreateLanguageHandler
{
    public function __construct(
        private LanguageRepositoryInterface $languageRepository,
        private ProjectRepositoryInterface $projectRepository,
    ) {
    }

    public function __invoke(CreateLanguageCommand $command): LanguageOutput
    {
        $input = $command->input;

        $project = $this->projectRepository->findById(Uuid::fromString($input->projectId));
        if ($project === null) {
            throw NotFoundException::forEntity('Project', $input->projectId);
        }

        $language = Language::create(
            name: $input->name,
            version: $input->version,
            detectedAt: new DateTimeImmutable($input->detectedAt),
            project: $project,
        );

        $this->languageRepository->save($language);

        return LanguageMapper::toOutput($language);
    }
}
