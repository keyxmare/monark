<?php

declare(strict_types=1);

use App\Catalog\Application\DTO\FrameworkOutput;
use App\Catalog\Application\Query\ListFrameworksQuery;
use App\Catalog\Application\QueryHandler\ListFrameworksHandler;
use App\Catalog\Domain\Model\Framework;
use App\Catalog\Domain\Repository\FrameworkRepositoryInterface;
use Symfony\Component\Uid\Uuid;
use Tests\Factory\Catalog\FrameworkFactory;

function stubListFrameworksRepo(array $frameworks = []): FrameworkRepositoryInterface
{
    return new class ($frameworks) implements FrameworkRepositoryInterface {
        public function __construct(private readonly array $frameworks)
        {
        }

        public function findById(Uuid $id): ?Framework
        {
            return null;
        }
        public function findAll(): array
        {
            return $this->frameworks;
        }
        public function findByProjectId(Uuid $projectId): array
        {
            return $this->frameworks;
        }
        public function findByLanguageId(Uuid $languageId): array
        {
            return [];
        }
        public function findByNameAndProjectId(string $name, Uuid $projectId): ?Framework
        {
            return null;
        }
        public function findByName(string $name): array
        {
            return [];
        }
        public function save(Framework $framework): void
        {
        }
        public function delete(Framework $framework): void
        {
        }
        public function deleteByProjectId(Uuid $projectId): void
        {
        }
    };
}

describe('ListFrameworksHandler', function () {
    it('returns all frameworks as output', function () {
        $fw1 = FrameworkFactory::create(name: 'Symfony', version: '7.1');
        $fw2 = FrameworkFactory::create(name: 'Laravel', version: '11.0');

        $handler = new ListFrameworksHandler(\stubListFrameworksRepo([$fw1, $fw2]));
        $result = $handler(new ListFrameworksQuery());

        expect($result)->toHaveCount(2)
            ->and($result[0])->toBeInstanceOf(FrameworkOutput::class)
            ->and($result[0]->name)->toBe('Symfony')
            ->and($result[1]->name)->toBe('Laravel');
    });

    it('returns empty array when no frameworks', function () {
        $handler = new ListFrameworksHandler(\stubListFrameworksRepo([]));
        $result = $handler(new ListFrameworksQuery());

        expect($result)->toBeEmpty();
    });

    it('filters by project when projectId is provided', function () {
        $fw = FrameworkFactory::create(name: 'Vue.js');
        $handler = new ListFrameworksHandler(\stubListFrameworksRepo([$fw]));

        $result = $handler(new ListFrameworksQuery(projectId: Uuid::v7()->toRfc4122()));

        expect($result)->toHaveCount(1)
            ->and($result[0]->name)->toBe('Vue.js');
    });

    it('includes languageName in output', function () {
        $fw = FrameworkFactory::create(name: 'Symfony');
        $handler = new ListFrameworksHandler(\stubListFrameworksRepo([$fw]));

        $result = $handler(new ListFrameworksQuery());

        expect($result[0]->languageName)->toBe('PHP');
    });
});
