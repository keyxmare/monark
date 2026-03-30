<?php

declare(strict_types=1);

use App\Catalog\Application\DTO\LanguageOutput;
use App\Catalog\Application\Query\ListLanguagesQuery;
use App\Catalog\Application\QueryHandler\ListLanguagesHandler;
use App\Catalog\Domain\Model\Language;
use App\Catalog\Domain\Repository\LanguageRepositoryInterface;
use Symfony\Component\Uid\Uuid;
use Tests\Factory\Catalog\LanguageFactory;

function stubListLanguagesRepo(array $languages = []): LanguageRepositoryInterface
{
    return new class ($languages) implements LanguageRepositoryInterface {
        public function __construct(private readonly array $languages)
        {
        }

        public function findById(Uuid $id): ?Language
        {
            return null;
        }
        public function findAll(): array
        {
            return $this->languages;
        }
        public function findByProjectId(Uuid $projectId): array
        {
            return $this->languages;
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

describe('ListLanguagesHandler', function () {
    it('returns all languages as output', function () {
        $lang1 = LanguageFactory::create(name: 'PHP', version: '8.4');
        $lang2 = LanguageFactory::create(name: 'TypeScript', version: '5.0');

        $handler = new ListLanguagesHandler(\stubListLanguagesRepo([$lang1, $lang2]));
        $result = $handler(new ListLanguagesQuery());

        expect($result)->toHaveCount(2)
            ->and($result[0])->toBeInstanceOf(LanguageOutput::class)
            ->and($result[0]->name)->toBe('PHP')
            ->and($result[1]->name)->toBe('TypeScript');
    });

    it('returns empty array when no languages', function () {
        $handler = new ListLanguagesHandler(\stubListLanguagesRepo([]));
        $result = $handler(new ListLanguagesQuery());

        expect($result)->toBeEmpty();
    });

    it('filters by project when projectId is provided', function () {
        $lang = LanguageFactory::create(name: 'Ruby');
        $repo = \stubListLanguagesRepo([$lang]);
        $handler = new ListLanguagesHandler($repo);

        $result = $handler(new ListLanguagesQuery(projectId: Uuid::v7()->toRfc4122()));

        expect($result)->toHaveCount(1)
            ->and($result[0]->name)->toBe('Ruby');
    });
});
