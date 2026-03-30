<?php

declare(strict_types=1);

use App\Catalog\Application\Command\DeleteLanguageCommand;
use App\Catalog\Application\CommandHandler\DeleteLanguageHandler;
use App\Catalog\Domain\Model\Language;
use App\Catalog\Domain\Repository\LanguageRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Uid\Uuid;
use Tests\Factory\Catalog\LanguageFactory;

function stubDeleteLanguageRepo(?Language $language = null): LanguageRepositoryInterface
{
    return new class ($language) implements LanguageRepositoryInterface {
        public bool $deleted = false;

        public function __construct(private readonly ?Language $language)
        {
        }

        public function findById(Uuid $id): ?Language
        {
            return $this->language;
        }
        public function findAll(): array
        {
            return [];
        }
        public function findByProjectId(Uuid $projectId): array
        {
            return [];
        }
        public function findByNameAndProjectId(string $name, Uuid $projectId): ?Language
        {
            return null;
        }
        public function save(Language $language): void
        {
        }
        public function delete(Language $language): void
        {
            $this->deleted = true;
        }
        public function deleteByProjectId(Uuid $projectId): void
        {
        }
    };
}

describe('DeleteLanguageHandler', function () {
    it('deletes a language successfully', function () {
        $language = LanguageFactory::create();
        $repo = \stubDeleteLanguageRepo($language);

        $handler = new DeleteLanguageHandler($repo);
        $handler(new DeleteLanguageCommand($language->getId()->toRfc4122()));

        expect($repo->deleted)->toBeTrue();
    });

    it('throws NotFoundException when language does not exist', function () {
        $repo = \stubDeleteLanguageRepo(null);
        $handler = new DeleteLanguageHandler($repo);

        $handler(new DeleteLanguageCommand('00000000-0000-0000-0000-000000000000'));
    })->throws(NotFoundException::class);
});
