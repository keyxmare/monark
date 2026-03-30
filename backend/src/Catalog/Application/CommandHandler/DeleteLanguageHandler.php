<?php

declare(strict_types=1);

namespace App\Catalog\Application\CommandHandler;

use App\Catalog\Application\Command\DeleteLanguageCommand;
use App\Catalog\Domain\Repository\LanguageRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class DeleteLanguageHandler
{
    public function __construct(
        private LanguageRepositoryInterface $languageRepository,
    ) {
    }

    public function __invoke(DeleteLanguageCommand $command): void
    {
        $language = $this->languageRepository->findById(Uuid::fromString($command->id));
        if ($language === null) {
            throw NotFoundException::forEntity('Language', $command->id);
        }

        $this->languageRepository->delete($language);
    }
}
