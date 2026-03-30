<?php

declare(strict_types=1);

use App\Catalog\Application\DTO\LanguageOutput;
use App\Catalog\Application\Query\GetLanguageQuery;
use App\Catalog\Application\QueryHandler\GetLanguageHandler;
use App\Catalog\Domain\Model\Language;
use App\Catalog\Domain\Repository\LanguageRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Uid\Uuid;
use Tests\Factory\Catalog\LanguageFactory;

function stubGetLanguageRepo(?Language $language = null): LanguageRepositoryInterface
{
    return new class ($language) implements LanguageRepositoryInterface {
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
        }
        public function deleteByProjectId(Uuid $projectId): void
        {
        }
    };
}

describe('GetLanguageHandler', function () {
    it('returns a language output', function () {
        $language = LanguageFactory::create(name: 'PHP', version: '8.4');
        $handler = new GetLanguageHandler(\stubGetLanguageRepo($language));

        $result = $handler(new GetLanguageQuery($language->getId()->toRfc4122()));

        expect($result)->toBeInstanceOf(LanguageOutput::class)
            ->and($result->name)->toBe('PHP')
            ->and($result->version)->toBe('8.4');
    });

    it('throws NotFoundException when language does not exist', function () {
        $handler = new GetLanguageHandler(\stubGetLanguageRepo(null));

        $handler(new GetLanguageQuery('00000000-0000-0000-0000-000000000000'));
    })->throws(NotFoundException::class);
});
